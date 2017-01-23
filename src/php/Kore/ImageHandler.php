<?php

namespace Kore;

use \stojg\crop\CropBalanced;

class ImageHandler
{
    /**
     * @var int
     */
    private $dpi = 300;

    /**
     * @var int
     */
    private $quality = 95;

    public function setDpi(int $dpi)
    {
        $this->dpi = $dpi;
    }

    public function setQuality(int $quality)
    {
        $this->quality = $quality;
    }

    public function resize(string $path, int $width, int $height): string
    {
        $hash = md5(json_encode([$this->dpi, $this->quality, $path, $width, $height]));
        $target = __DIR__ . '/../../../var/cache/' . $hash . '.jpeg';
        if (file_exists($target)) {
            return $target;
        }

        $imagick = new \Imagick($path);
        $imagick->setImageCompressionQuality($this->quality);

        $cropper = new CropBalanced($path);
        $cropped = $cropper->resizeAndCrop(
            $width * 0.0393701 * $this->dpi,
            $height * 0.0393701 * $this->dpi
        );

        $cropped->writeImage($target);
        return $target;
    }
}
