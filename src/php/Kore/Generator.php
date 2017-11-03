<?php

namespace Kore;

use Symfony\Component\Yaml\Yaml;

class Generator
{
    private $imageHandler;

    private $templateHandler;

    private $pageTypes = [];

    public function __construct(
        ImageHandler $imageHandler = null,
        TemplateHandler $templateHandler = null,
        array $pageTypes = []
    ) {
        $this->imageHandler = $imageHandler ?: new ImageHandler();
        $this->templateHandler = $templateHandler ?: new TemplateHandler();
        $this->pageTypes = $pageTypes ?: [
            new Page\Photo($this->templateHandler, $this->imageHandler),
            new Page\TwoStacked($this->templateHandler, $this->imageHandler),
            new Page\Caption($this->templateHandler, $this->imageHandler),
            new Page\Travel($this->templateHandler, $this->imageHandler),
            new Page\ClearDouble($this->templateHandler),
            new Page\Spread($this->templateHandler, $this->imageHandler),
            new Page\Portrait($this->templateHandler, $this->imageHandler),
            new Page\Panel($this->templateHandler, $this->imageHandler),
            new Page\Grid($this->templateHandler, $this->imageHandler),
        ];
    }

    public function addPageType(Page $pageType)
    {
        $this->pageTypes[] = $pageType;
    }

    public function fromYamlFile($file): Book
    {
        $configuration = Yaml::parse(file_get_contents($file));

        $book = new Book($configuration['book']);
        if (!$book->production) {
            $this->imageHandler->setDpi(90);
            $this->imageHandler->setQuality(80);
        }

        echo "Processing pages: ";
        $number = 1;
        foreach ($configuration['pages'] as $definition) {
            echo ".";
            foreach ($this->pageTypes as $pageType) {
                if ($pageType->handles($definition)) {
                    $page = $pageType->create($book, $definition, $number);
                    $page->source = $definition;
                    $page->number = $number;
                    $page->right = (bool) ($number % 2);

                    if (!$page instanceof Book\Page\None) {
                        ++$number;
                        $book->pages[] = $page;
                    }
                    continue 2;
                }
            }

            throw new \OutOfBoundsException("No page type for: " . json_encode($page));
        }
        echo PHP_EOL;

        return $book;
    }

    public function writePdf(Book $book, $targetFile)
    {
        if (count($book->pages) % 4) {
            echo "Warning: Book contains ", count($book->pages), " pages, which is not divisible by 4.", PHP_EOL;
        }

        $dpi = $book->production ? 300 : 90;
        foreach ($book->pages as $number => $page) {
            if ($page->svg && !$page->pdf) {
                $page->pdf = sprintf(__DIR__ . '/../../../var/page-%03d.pdf', $number);

                if (!file_exists($page->svg)) {
                    throw new \RuntimeException("Cannot handle page $number (" . json_encode($page->source) . ") – file not existant.");
                }

                if (!$book->production) {
                    $marks = $this->templateHandler->render('svg/cutOff.svg.twig', ['book' => $book, 'page' => $page]);
                    file_put_contents(
                        $page->svg,
                        str_replace(
                            '</svg>',
                            $marks . '</svg>',
                            file_get_contents($page->svg)
                        )
                    );
                }

                exec("inkscape --export-dpi=$dpi --export-text-to-path --export-area-page --export-pdf={$page->pdf} {$page->svg}");
                // unlink($page->svg);
            }
        }

        exec("pdftk " . implode(' ', array_map(
            function (Book\Page $page) {
                return $page->pdf;
            },
            $book->pages)) . " cat output " . $targetFile
        );
    }
}
