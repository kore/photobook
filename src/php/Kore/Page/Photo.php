<?php

namespace Kore\Page;

use Kore\ImageHandler;
use Kore\Page;
use Kore\Book;

class Photo extends Page
{
    private $imageHandler;

    public function __construct(ImageHandler $imageHandler)
    {
        $this->imageHandler = $imageHandler ?: new ImageHandler();
    }

    public function handles($mixed): bool
    {
        return is_string($mixed);
    }

    public function create(Book $book, $mixed): Book\Page
    {
        $path = $book->baseDir . '/' . $mixed;
        if (!file_exists($path)) {
            throw new \OutOfBoundException("File $path could not be found");
        }

        $imageFile = $this->imageHandler->resize($path, $book->format->width, $book->format->height);
        $data = [
            'link' => $path,
            'width' => $book->format->width,
            'height' => $book->format->height,
            'cutOff' => $book->format->cutOff,
            'innerWidth' => $book->format->width - 2 * $book->format->cutOff,
            'innerHeight' => $book->format->height - 2 * $book->format->cutOff,
            'path' => $imageFile,
        ];

        file_put_contents(
            $svgFile = __DIR__ . '/../../../../var/cache/' . $mixed . '.svg',
            str_replace(
                array_map(
                    function (string $key) {
                        return '{' . $key . '}';
                    },
                    array_keys($data)
                ),
                array_values($data),
                file_get_contents(__DIR__ . '/Photo/template.svg')
            )
        );

        return new Book\Page(['svg' => $svgFile]);
    }
}
