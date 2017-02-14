<?php

namespace Kore\Page;

use Kore\ImageHandler;
use Kore\TemplateHandler;
use Kore\Page;
use Kore\Book;

class ClearDouble extends Page
{
    private $templateHandler;

    public function __construct(TemplateHandler $templateHandler)
    {
        $this->templateHandler = $templateHandler;
    }

    public function handles($mixed): bool
    {
        return is_array($mixed) &&
            $mixed['type'] === 'cleardoublepage';
    }

    public function create(Book $book, $mixed, int $pageNumber): Book\Page
    {
        if ($pageNumber % 2) {
            return new Book\Page\None();
        }

        $data = [
            'book' => $book,
        ];

        file_put_contents(
            $svgFile = __DIR__ . '/../../../../var/cache/cleardoublepage_' . $pageNumber . '.svg',
            $this->templateHandler->render('svg/clearDouble.svg.twig', $data)
        );

        return new Book\Page(['svg' => $svgFile]);
    }
}
