<?php

namespace Kore\Page;

use Kore\Book;
use Kore\ImageHandler;
use Kore\Page;
use Kore\TemplateHandler;

class TwoStacked extends Page
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
            && 'stacked' === $mixed['type']
            && isset($mixed['photos'])
            && 2 === count($mixed['photos']);
    }

    public function create(Book $book, $mixed, int $pageNumber): Book\Page
    {
        $data = [
            'book' => $book,
            'photos' => array_map(
                function (string $path) use ($book) {
                    return $this->imageHandler->resize(
                        $book->baseDir.'/'.$path,
                        (int) $book->format->width / 2,
                        (int) $book->format->height / 2
                    );
                },
                $mixed['photos']
            ),
            'texts' => array_map(
                function (string $text) {
                    return preg_split(
                        '(\\r\\n|\\r|\\n)',
                        wordwrap($text, 40)
                    );
                },
                $mixed['texts'] ?? []
            ),
        ];

        file_put_contents(
            $svgFile = __DIR__.'/../../../../var/cache/'.hash('sha256', json_encode($mixed)).'.svg',
            $this->templateHandler->render('svg/twoStacked.svg.twig', $data)
        );

        return new Book\Page(['svg' => $svgFile, 'reference' => $mixed['photos'][0]]);
    }
}
