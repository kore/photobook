<?php

namespace Kore\Page;

use Kore\ImageHandler;
use Kore\TemplateHandler;
use Kore\Page;
use Kore\Book;

class Portrait extends Page
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
        return is_array($mixed) &&
            $mixed['type'] === 'portrait' &&
            isset($mixed['photos']) &&
            count($mixed['photos']) === 2;
    }

    public function create(Book $book, $mixed, int $pageNumber): Book\Page
    {
        $backgroundImage = null;
        if (isset($mixed['background'])) {
            $backgroundImage = $this->imageHandler->resize(
                $book->baseDir . '/' . $mixed['background'],
                $book->format->width,
                $book->format->height
            );
            $backgroundImage = $this->imageHandler->blur($backgroundImage);
        }

        $size = (object) [
            'width' => $book->format->width / 2 - $book->format->width / 10,
            'height' => $book->format->height - $book->format->height / 5,
        ];

        $data = [
            'book' => $book,
            'background' => $backgroundImage,
            'size' => $size,
            'photos' => array_map(
                function (string $path) use ($book, $size) {
                    return $this->imageHandler->resize(
                        $book->baseDir . '/' . $path,
                        $size->width,
                        $size->height
                    );
                },
                $mixed['photos']
            ),
        ];

        file_put_contents(
            $svgFile = __DIR__ . '/../../../../var/cache/' . hash("sha256", json_encode($mixed)) . '.svg',
            $this->templateHandler->render('svg/portrait.svg.twig', $data)
        );

        return new Book\Page([
            'svg' => $svgFile,
            'reference' => 'BG: ' . ($mixed['background'] ?? 'none'),
        ]);
    }
}
