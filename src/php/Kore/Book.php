<?php

namespace Kore;

use Kore\DataObject\DataObject;

class Book extends DataObject
{
    public $title = 'New Photo Book';
    public $baseDir;
    public $format;
    public $production = false;
    public $dpi = null;
    public $quality = null;
    public $pages = [];

    public function __construct(array $properties = [])
    {
        parent::__construct($properties);

        $this->format = new Book\Format($this->format);
    }
}
