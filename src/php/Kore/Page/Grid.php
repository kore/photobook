<?php

namespace Kore\Page;

use Kore\ImageHandler;
use Kore\TemplateHandler;
use Kore\Page;
use Kore\Book;

class Grid extends Page
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
            $mixed['type'] === 'grid' &&
            isset($mixed['photos']) &&
            is_array($mixed['photos']) &&
            isset($mixed['rows']) &&
            is_array($mixed['rows']);
    }

    public function create(Book $book, $mixed, int $pageNumber): Book\Page
    {
        if (array_sum($mixed['rows']) !== count($mixed['photos'])) {
            throw new \OutOfBoundsException(sprintf(
                '%d grid fields defined, but %d photos given.',
                array_sum($mixed['rows']),
                count($mixed['photos'])
            ));
        }
        $border = $mixed['border'] ?? 1;
        $rows = [];
        $height = ($book->format->height - ((count($mixed['rows']) - 1) * $border)) / count($mixed['rows']);
        $photo = 0;
        foreach ($mixed['rows'] as $row => $pictures) {
            $width = ($book->format->width - (($pictures - 1) * $border)) / $pictures;
            for ($i = 0; $i < $pictures; ++$i) {
                $rows[$row][] = (object) [
                    'width' => $width,
                    'height' => $height,
                    'photo' =>  $this->imageHandler->resize(
                        $book->baseDir . '/' . $mixed['photos'][$photo++],
                        $width,
                        $height
                    ),
                ];
            }
        }

        $data = [
            'book' => $book,
            'border' => $border,
            'borderColor' => $mixed['borderColor'] ?? '#ffffff',
            'rows' => $rows,
            'rowHeight' => $height,
        ];

        file_put_contents(
            $svgFile = __DIR__ . '/../../../../var/cache/' . hash("sha256", json_encode($mixed)) . '.svg',
            $this->templateHandler->render('svg/grid.svg.twig', $data)
        );

        return new Book\Page([
            'svg' => $svgFile,
            'reference' => 'Grid: ' . implode(' / ', $mixed['rows']),
        ]);
    }
}
