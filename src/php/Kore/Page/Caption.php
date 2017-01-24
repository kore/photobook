<?php

namespace Kore\Page;

use Kore\ImageHandler;
use Kore\TemplateHandler;
use Kore\Page;
use Kore\Book;

class Caption extends Page
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
            $mixed['type'] === 'caption';
    }

    public function create(Book $book, $mixed): Book\Page
    {
        $data = [
            'book' => $book,
            'photo' => $book->baseDir . '/' . $mixed['photo'],
            'caption' => $mixed['caption'],
            'position' => $mixed['position'] ?? .5,
        ];

        file_put_contents(
            $svgFile = __DIR__ . '/../../../../var/cache/' . md5(json_encode($mixed)) . '.svg',
            $this->templateHandler->render('Kore/Page/Caption/template.svg', $data)
        );

        return new Book\Page(['svg' => $svgFile]);
    }
}
