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

        foreach ($configuration['pages'] as $page) {
            foreach ($this->pageTypes as $pageType) {
                if ($pageType->handles($page)) {
                    $book->pages[] = $pageType->create($book, $page);
                    continue 2;
                }
            }

            throw new \OutOfBoundsException("No page type for: " . json_encode($page));
        }

        return $book;
    }

    public function writePdf(Book $book, $targetFile)
    {
        $dpi = $book->production ? 300 : 90;
        foreach ($book->pages as $nr => $page) {
            if ($page->svg && !$page->pdf) {
                $page->pdf = sprintf(__DIR__ . '/../../../var/page-%03d.pdf', $nr);
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
