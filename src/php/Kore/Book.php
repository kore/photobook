<?php

namespace Kore;

use Kore\DataObject\DataObject;

class Book extends DataObject
{
    public $title;
    public $baseDir;
    public $format;
    public $pages = [];

    public function __construct(array $properties = array())
    {
        parent::__construct($properties);

        $this->format = new Book\Format($this->format);
    }
}
