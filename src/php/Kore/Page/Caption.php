<?php

namespace Kore\Page;

use Kore\Book;
use Kore\ImageHandler;
use Kore\Page;
use Kore\TemplateHandler;

class Caption extends Page
{
    private $templateHandler;

    private $imageHandler;

    public function __construct(TemplateHandler $templateHandler, ImageHandler $imageHandler)
    {
        $this->templateHandler = $templateHandler;
        $this->imageHandler = $imageHandler;
    }

    public function handles($mixed): bool
    {
        return is_array($mixed)
            && 'caption' === $mixed['type'];
    }

    public function create(Book $book, $mixed, int $pageNumber): Book\Page
    {
        $imageFile = $this->imageHandler->resize(
            $book->baseDir.'/'.$mixed['photo'],
            $book->format->width,
            $book->format->height
        );

        $data = [
            'book' => $book,
            'photo' => $imageFile,
            'caption' => $mixed['caption'],
            'position' => $mixed['position'] ?? .5,
        ];

        file_put_contents(
            $svgFile = __DIR__.'/../../../../var/cache/'.hash('sha256', json_encode($mixed)).'.svg',
            $this->templateHandler->render('svg/caption.svg.twig', $data)
        );

        return new Book\Page(['svg' => $svgFile, 'reference' => $mixed['caption']]);
    }
}
