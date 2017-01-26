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
        foreach ($configuration['pages'] as $number => $definition) {
            echo ".";
            foreach ($this->pageTypes as $pageType) {
                if ($pageType->handles($definition)) {
                    $page = $pageType->create($book, $definition, $number);
                    $page->source = $definition;
                    if (!$page instanceof Book\Page\None) {
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
        $dpi = $book->production ? 300 : 90;
        foreach ($book->pages as $nr => $page) {
            if ($page->svg && !$page->pdf) {
                $page->pdf = sprintf(__DIR__ . '/../../../var/page-%03d.pdf', $nr);

                if (!file_exists($page->svg)) {
                    throw new \RuntimeException("Cannot handle page $nr (" . json_encode($page->source) . ") – file not existant.");
                }

                exec("inkscape --export-dpi=$dpi --export-area-page --export-pdf={$page->pdf} {$page->svg}");
                unlink($page->svg);
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
