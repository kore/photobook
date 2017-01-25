<?php

namespace Kore\Page;

use Kore\ImageHandler;
use Kore\TemplateHandler;
use Kore\Page;
use Kore\Book;

class Travel extends Page
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
            $mixed['type'] === 'travel';
    }

    public function create(Book $book, $mixed, int $pageNumber): Book\Page
    {
        $imageFile = $this->imageHandler->resize(
            $book->baseDir . '/' . $mixed['photo'],
            $book->format->width,
            $book->format->height
        );
        $imageFile = $this->imageHandler->blur($imageFile);

        $data = [
            'book' => $book,
            'photo' => $imageFile,
            'from' => $mixed['from'],
            'symbols' => $mixed['symbols'] ?? [],
            'to' => $mixed['to'],
            'date' => isset($mixed['date']) ? new \DateTime($mixed['date']) :null,
            'position' => $mixed['position'] ?? .5,
        ];

        file_put_contents(
            $svgFile = __DIR__ . '/../../../../var/cache/' . md5(json_encode($mixed)) . '.svg',
            $this->templateHandler->render('Kore/Page/Travel/template.svg', $data)
        );

        return new Book\Page(['svg' => $svgFile]);
    }
}
