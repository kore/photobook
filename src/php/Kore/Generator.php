<?php

namespace Kore;

use Symfony\Component\Yaml\Yaml;

class Generator
{
    private $pageTypes = [];

    public function __construct(array $pageTypes = [])
    {
        $this->pageTypes = $pageTypes ?: [
            new Page\Photo(),
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

    public function getPdf(Book $book)
    {
        return null;
    }
}
