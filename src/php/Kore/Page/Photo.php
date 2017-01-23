<?php

namespace Kore\Page;

use Kore\Page;
use Kore\Book;

class Photo extends Page
{
    public function handles($mixed): bool
    {
        return is_string($mixed);
    }

    public function create($mixed): Book\Page
    {
        return new Book\Page();
    }
}
