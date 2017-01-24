<?php

namespace Kore\Book;

use Kore\DataObject\DataObject;

class Format extends DataObject
{
    public $width = 297;
    public $height = 210;
    public $cutOff = 0;
    public $font = 'Cantarell';
    public $fontSize = 6;
    public $titleFontSize = 18;
    public $subTitleFontSize = 12;
}
