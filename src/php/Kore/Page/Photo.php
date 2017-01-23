<?php

namespace Kore\Page;

use Kore\Page;
use Kore\Book;

class Photo extends Page
{
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

        $meta = getimagesize($path);
        $data = [
            'link' => $path,
            'width' => $book->format->width,
            'height' => $book->format->height,
            'cutOff' => $book->format->cutOff,
            'innerWidth' => $book->format->width - 2 * $book->format->cutOff,
            'innerHeight' => $book->format->height - 2 * $book->format->cutOff,
            'actualWidth' => $book->format->width,
            'actualHeight' => $book->format->height,
            'offsetX' => 0,
            'offsetY' => 0,
            'path' => $path,
        ];

        if (abs(($meta[0] / $meta[1]) - ($book->format->width / $book->format->height)) < .001) {
            // Settings should be OK
        } else if (($meta[0] / $meta[1]) > ($book->format->width / $book->format->height)) {
            echo "Notice: Photo $mixed is too wide, copping.", PHP_EOL;
            $scaleFactor = $meta[1] / $book->format->height;
            $data['actualWidth'] = $meta[0] / $scaleFactor;
            $data['offsetX'] = -($data['actualWidth'] - $data['width']) / 2;
        } else {
            echo "Notice: Photo $mixed is too high, copping.", PHP_EOL;
            $scaleFactor = $meta[0] / $book->format->width;
            $data['actualHeight'] = $meta[1] / $scaleFactor;
            $data['offsetY'] = -($data['actualHeight'] - $data['height']) / 2;
        }

        $svg = str_replace(
            array_map(
                function (string $key) {
                    return '{' . $key . '}';
                },
                array_keys($data)
            ),
            array_values($data),
            file_get_contents(__DIR__ . '/Photo/template.svg')
        );
        file_put_contents(
            __DIR__ . '/../../../../var/cache/' . $mixed . '.svg',
            $svg
        );

        return new Book\Page();
    }
}
