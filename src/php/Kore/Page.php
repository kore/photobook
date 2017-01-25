<?php

namespace Kore;

abstract class Page
{
    abstract public function handles($mixed): bool;

    abstract public function create(Book $book, $mixed, int $pageNumber): Book\Page;
}
