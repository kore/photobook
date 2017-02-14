<?php

namespace Kore\Book;

use Kore\DataObject\DataObject;

class Page extends DataObject
{
    public $reference = '';
    public $number;
    public $right;
    public $svg;
    public $pdf;
    public $source;
}
