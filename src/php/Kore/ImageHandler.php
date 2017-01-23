<?php

namespace Kore;

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
        if (false && file_exists($target)) {
            return $target;
        }

        $imagick = new \Imagick($path);
        $imagick->setImageCompressionQuality($this->quality);

        $inputRatio = $imagick->getImageWidth() / $imagick->getImageHeight();
        $outputRatio = $width / $height;

        $scaleFactor = $inputRatio / $outputRatio;
        if ($inputRatio > $outputRatio) {
            $imagick->cropImage(
                $imagick->getImageWidth() / $scaleFactor,
                $imagick->getImageHeight(),
                ($imagick->getImageWidth() - ($imagick->getImageWidth() / $scaleFactor)) / 2,
                0
            );
        } elseif ($outputRatio > $inputRatio) {
            $imagick->cropImage(
                $imagick->getImageWidth(),
                $imagick->getImageHeight() * $scaleFactor,
                0,
                ($imagick->getImageHeight() - ($imagick->getImageHeight() * $scaleFactor)) / 2
            );
        }

        $imagick->resizeImage(
            $width * 0.0393701 * $this->dpi,
            $height * 0.0393701 * $this->dpi,
            \Imagick::FILTER_LANCZOS,
            1,
            true
        );

        $imagick->writeImage($target);
        return $target;
    }
}
