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
            isset($mixed['photos']) && is_array($mixed['photos']) &&
            (
                (isset($mixed['rows']) && is_array($mixed['rows'])) ||
                (isset($mixed['columns']) && is_array($mixed['columns']))
            );
    }

    public function create(Book $book, $mixed, int $pageNumber): Book\Page
    {
        if (isset($mixed['rows'])) {
            $svgFile = $this->renderRows($book, $mixed);
        } else {
            $svgFile = $this->renderColumns($book, $mixed);
        }

        return new Book\Page([
            'svg' => $svgFile,
            'reference' => 'Grid: ' . implode(' / ', $mixed['rows'] ?? $mixed['columns']),
        ]);
    }

    protected function renderRows(Book $book, array $mixed): string
    {
        if (array_sum($mixed['rows']) !== count($mixed['photos'])) {
            throw new \OutOfBoundsException(sprintf(
                '%d grid fields defined, but %d photos given.',
                array_sum($mixed['rows']),
                count($mixed['photos'])
            ));
        }

        $rows = [];
        $border = $mixed['border'] ?? 1;
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

        return $svgFile;
    }

    protected function renderColumns(Book $book, array $mixed): string
    {
        if (array_sum($mixed['columns']) !== count($mixed['photos'])) {
            throw new \OutOfBoundsException(sprintf(
                '%d grid fields defined, but %d photos given.',
                array_sum($mixed['columns']),
                count($mixed['photos'])
            ));
        }

        $columns = [];
        $border = $mixed['border'] ?? 1;
        $width = ($book->format->width - ((count($mixed['columns']) - 1) * $border)) / count($mixed['columns']);
        $photo = 0;
        foreach ($mixed['columns'] as $column => $pictures) {
            $height = ($book->format->height - (($pictures - 1) * $border)) / $pictures;
            for ($i = 0; $i < $pictures; ++$i) {
                $columns[$column][] = (object) [
                    'height' => $height,
                    'width' => $width,
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
            'columns' => $columns,
            'columnWidth' => $width,
        ];

        file_put_contents(
            $svgFile = __DIR__ . '/../../../../var/cache/' . hash("sha256", json_encode($mixed)) . '.svg',
            $this->templateHandler->render('svg/grid.svg.twig', $data)
        );

        return $svgFile;
    }
}
