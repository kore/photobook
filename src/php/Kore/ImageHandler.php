<?php

namespace Kore;

class ImageHandler
{
    /**
     * @var int
     */
    private int $dpi = 300;

    /**
     * @var int
     */
    private int $quality = 90;

    private const DPI_PIXEL_SCALE_FACTOR = 0.0393701;

    private bool $detectFaces = false;

    public function __construct()
    {
        // This is expcted to have return code 64 (missing argument) and will
        // fail with other (mostly 1) return codes if some dependency is not
        // available, and therefore face detection won't work.
        exec(__DIR__ . '/../../../bin/extractFaces', $output, $returnCode);
        $this->detectFaces = $returnCode === 64;

        if ($returnCode !== 64) {
            echo "Warning: Face detection disabled, bin/extractFaces probably misses some dependency.", PHP_EOL;
        }
    }

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
        $hash = hash('sha256', json_encode([md5_file($path), $this->dpi, $this->quality, $path, $width, $height]));
        $target = __DIR__.'/../../../var/cache/'.$hash.'.png';
        if (file_exists($target)) {
            return $target;
        }

        $width = (int) ($width * $this->dpi * self::DPI_PIXEL_SCALE_FACTOR);
        $height = (int) ($height * $this->dpi * self::DPI_PIXEL_SCALE_FACTOR);

        $center = null;
        $cachedCenter = __DIR__.'/../../../var/cache/'.$hash.'-center.json';
        if (file_exists($cachedCenter)) {
            $center = json_decode(file_get_contents($cachedCenter));
        }

        if (!$center && $this->detectFaces) {
            $scaledDownTarget = __DIR__.'/../../../var/cache/'.$hash.'-facedetect.jpg';
            $imagick = new \Imagick($path);
            $this->autoRotateImage($imagick);
            $imagick->scaleImage(1024, 1024, true);
            $imagick->setImageFormat('jpeg');
            $imagick->writeImage($scaledDownTarget);

            $faces = json_decode(shell_exec(__DIR__ . '/../../../bin/extractFaces ' . escapeshellarg($scaledDownTarget)));
            unlink($scaledDownTarget);

            if ($faces) {
                foreach ($faces as $face) {
                    $center['x'][] = $face->x;
                    $center['x'][] = $face->x + $face->width;
                    $center['y'][] = $face->y;
                    $center['y'][] = $face->y + $face->height;
                }

                $center['x'] = array_sum($center['x']) / count($center['x']) / $imagick->getImageWidth();
                $center['y'] = array_sum($center['y']) / count($center['y']) / $imagick->getImageHeight();
            }

            file_put_contents($cachedCenter, json_encode($center));
        }

        if (!$center) {
            $center = ['x' => .5, 'y' => .5];
        }

        $imagick = new \Imagick($path);
        $this->autoRotateImage($imagick);
        $imagick->setImageCompressionQuality($this->quality);

        // Find maximum area in image matching the target aspect ratio
        $originalAspectRatio = $imagick->getImageWidth() / $imagick->getImageHeight();
        $targetAspectRatio = $width / $height;
        if ($originalAspectRatio < $targetAspectRatio) {
            $targetWidth = $imagick->getImageWidth();
            $targetHeight = (int) ($imagick->getImageWidth() / $targetAspectRatio);
        } else {
            $targetWidth = (int) ($imagick->getImageHeight() * $targetAspectRatio);
            $targetHeight = $imagick->getImageHeight();
        }

        // Find best offset based on target center point
        $croppingPercentage = min(
            $imagick->getImageWidth() / $targetWidth,
            $imagick->getImageHeight() / $targetHeight,
        );
        $offsetX = ($imagick->getImageWidth() - $targetWidth) * $center['x'];
        $offsetY = ($imagick->getImageHeight() - $targetHeight) * $center['y'];

        // First crop, then scale down
        $imagick->cropImage($targetWidth, $targetHeight, $offsetX, $offsetY);
        $imagick->scaleImage($width, $height);
        $imagick->setImageFormat('png32');
        $imagick->writeImage($target);

        return $target;
    }

    public function fit(string $path, int $width, int $height): string
    {
        $hash = hash('sha256', json_encode([md5_file($path), $this->dpi, $this->quality, $path, $width, $height, 'Fit']));
        $target = __DIR__.'/../../../var/cache/'.$hash.'.png';
        if (file_exists($target)) {
            return $target;
        }

        $imagick = new \Imagick();
        $this->autoRotateImage($imagick);
        $imagick->setBackgroundColor(new \ImagickPixel('transparent'));
        $imagick->readImage($path);
        $imagick->setImageCompressionQuality($this->quality);

        $xScaleFactor = $imagick->getImageWidth / ($width * self::DPI_PIXEL_SCALE_FACTOR * $this->dpi);
        $yScaleFactor = $imagick->getImageHeight / ($height * self::DPI_PIXEL_SCALE_FACTOR * $this->dpi);
        $scaleFactor = max($xScaleFactor, $yScaleFactor, 1);

        $imagick->resizeImage(
            $imagick->getImageWidth() / $scaleFactor,
            $imagick->getImageHeight() / $scaleFactor,
            \Imagick::FILTER_LANCZOS,
            1
        );

        $imagick->setImageFormat('png32');
        $imagick->writeImage($target);
        return $target;
    }

    public function blur(string $path)
    {
        $hash = hash('sha256', json_encode([md5_file($path), $this->dpi, $this->quality, $path, 'blurred']));
        $target = __DIR__.'/../../../var/cache/'.$hash.'.jpeg';
        if (file_exists($target)) {
            return $target;
        }

        $imagick = new \Imagick($path);
        $this->autoRotateImage($imagick);
        $imagick->setImageCompressionQuality($this->quality);

        $imagick->blurImage($this->dpi / 5, $this->dpi / 10);
        $imagick->writeImage($target);
        return $target;
    }

    protected function autoRotateImage(\Imagick $imagick)
    {
        $orientation = $imagick->getImageOrientation();

        switch ($orientation) {
            case \Imagick::ORIENTATION_BOTTOMRIGHT:
                $imagick->rotateImage('#000', 180);
                break;

            case \Imagick::ORIENTATION_RIGHTTOP:
                $imagick->rotateImage('#000', 90);
                break;

            case \Imagick::ORIENTATION_LEFTBOTTOM:
                $imagick->rotateImage('#000', -90);
                break;
        }

        // Now that it's auto-rotated, make sure the EXIF data is correct in
        // case the EXIF gets saved with the image
        $imagick->setImageOrientation(\Imagick::ORIENTATION_TOPLEFT);
    }
}
