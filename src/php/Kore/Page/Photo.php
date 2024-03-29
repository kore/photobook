<?php

namespace Kore\Page;

use Kore\Book;
use Kore\ImageHandler;
use Kore\Page;
use Kore\TemplateHandler;

class Photo extends Page
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
        return is_string($mixed);
    }

    public function create(Book $book, $mixed, int $pageNumber): Book\Page
    {
        $path = $book->baseDir.'/'.$mixed;
        if (!file_exists($path)) {
            throw new \OutOfBoundsException("File $path could not be found");
        }

        $imageFile = $this->imageHandler->resize($path, $book->format->width, $book->format->height);
        $data = [
            'book' => $book,
            'image' => $imageFile,
        ];

        file_put_contents(
            $svgFile = __DIR__.'/../../../../var/cache/'.md5($mixed).'.svg',
            $this->templateHandler->render('svg/photo.svg.twig', $data)
        );

        return new Book\Page(['svg' => $svgFile, 'reference' => $mixed]);
    }
}
