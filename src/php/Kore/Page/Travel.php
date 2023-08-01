<?php

namespace Kore\Page;

use Kore\Book;
use Kore\ImageHandler;
use Kore\Page;
use Kore\TemplateHandler;

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
        return is_array($mixed)
            && 'travel' === $mixed['type'];
    }

    public function create(Book $book, $mixed, int $pageNumber): Book\Page
    {
        $imageFile = $this->imageHandler->resize(
            $book->baseDir.'/'.$mixed['photo'],
            $book->format->width,
            $book->format->height
        );
        $imageFile = $this->imageHandler->blur($imageFile);

        $bottomImageFile = null;
        if (isset($mixed['image'])) {
            $bottomImageFile = $this->imageHandler->fit(
                $book->baseDir.'/'.$mixed['image'],
                (int) $book->format->width * .9,
                (int) $book->format->height * .45
            );
        }

        $data = [
            'book' => $book,
            'photo' => $imageFile,
            'from' => $mixed['from'],
            'symbols' => $mixed['symbols'] ?? [],
            'to' => $mixed['to'],
            'image' => $bottomImageFile,
            'date' => isset($mixed['date']) ? new \DateTime($mixed['date']) : null,
        ];

        file_put_contents(
            $svgFile = __DIR__.'/../../../../var/cache/'.hash('sha256', json_encode($mixed)).'.svg',
            $this->templateHandler->render('svg/travel.svg.twig', $data)
        );

        return new Book\Page(['svg' => $svgFile, 'reference' => $mixed['from'].' to '.$mixed['to']]);
    }
}
