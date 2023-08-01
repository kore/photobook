<?php

namespace Kore\Page;

use Kore\Book;
use Kore\ImageHandler;
use Kore\Page;
use Kore\TemplateHandler;

class Panel extends Page
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
            && 'panel' === $mixed['type']
            && isset($mixed['photos'])
            && count($mixed['photos']) >= 2
            && count($mixed['photos']) <= 4;
    }

    public function create(Book $book, $mixed, int $pageNumber): Book\Page
    {
        $horizontal = !isset($mixed['orientation']) || ('vertical' !== $mixed['orientation']);
        $border = $mixed['border'] ?? 1;

        $size = (object) [
            'width' => ($book->format->width - ($horizontal ? (count($mixed['photos']) - 1 * $border) : 0)) / ($horizontal ? count($mixed['photos']) : 1),
            'height' => ($book->format->height - (!$horizontal ? (count($mixed['photos']) - 1 * $border) : 0)) / (!$horizontal ? count($mixed['photos']) : 1),
        ];

        $data = [
            'book' => $book,
            'size' => $size,
            'border' => $border,
            'borderColor' => $mixed['borderColor'] ?? '#ffffff',
            'orientation' => $horizontal ? 'horizontal' : 'vertical',
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
            $this->templateHandler->render('svg/panel.svg.twig', $data)
        );

        return new Book\Page([
            'svg' => $svgFile,
            'reference' => 'Panel: '.($horizontal ? 'Horizontal ' : 'Vertical ').count($mixed['photos']),
        ]);
    }
}
