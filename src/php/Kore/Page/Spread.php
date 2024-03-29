<?php

namespace Kore\Page;

use Kore\Book;
use Kore\ImageHandler;
use Kore\Page;
use Kore\TemplateHandler;

class Spread extends Page
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
            && 'spread' === $mixed['type']
            && isset($mixed['photos'])
            && count($mixed['photos']) >= 2
            && count($mixed['photos']) <= 4;
    }

    public function create(Book $book, $mixed, int $pageNumber): Book\Page
    {
        $border = 40;
        $backgroundImage = null;
        if (isset($mixed['background'])) {
            $backgroundImage = $this->imageHandler->resize(
                $book->baseDir.'/'.$mixed['background'],
                $book->format->width,
                $book->format->height
            );
            $backgroundImage = $this->imageHandler->blur($backgroundImage);
            $border = 20;
        }

        $size = (object) [
            'width' => $book->format->width / 2 - $book->format->width / $border * 2,
            'height' => $book->format->height / 2 - $book->format->height / $border * 2,
        ];

        $data = [
            'book' => $book,
            'background' => $backgroundImage,
            'size' => $size,
            'border' => $border,
            'photos' => array_map(
                function (string $path) use ($book, $size) {
                    return $this->imageHandler->resize(
                        $book->baseDir.'/'.$path,
                        (int) $size->width,
                        (int) $size->height
                    );
                },
                $mixed['photos']
            ),
        ];

        file_put_contents(
            $svgFile = __DIR__.'/../../../../var/cache/'.hash('sha256', json_encode($mixed)).'.svg',
            $this->templateHandler->render('svg/spread.svg.twig', $data)
        );

        return new Book\Page([
            'svg' => $svgFile,
            'reference' => 'BG: '.($mixed['background'] ?? 'none'),
        ]);
    }
}
