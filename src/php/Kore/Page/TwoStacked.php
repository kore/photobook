<?php

namespace Kore\Page;

use Kore\ImageHandler;
use Kore\TemplateHandler;
use Kore\Page;
use Kore\Book;

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
        return is_array($mixed) &&
            $mixed['type'] === 'stacked' &&
            count($mixed['photos']) === 2;
    }

    public function create(Book $book, $mixed, int $pageNumber): Book\Page
    {
        $data = [
            'book' => $book,
            'photos' => array_map(
                function (string $path) use ($book) {
                    return $this->imageHandler->resize(
                        $book->baseDir . '/' . $path,
                        $book->format->width / 2,
                        $book->format->height / 2
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
            $svgFile = __DIR__ . '/../../../../var/cache/' . md5(json_encode($mixed)) . '.svg',
            $this->templateHandler->render('Kore/Page/TwoStacked/template.svg', $data)
        );

        return new Book\Page(['svg' => $svgFile]);
    }
}
