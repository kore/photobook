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

    private const DPI_PIXEL_SCALE_FACTOR = 0.0393701;

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
        $hash = hash('sha256', json_encode([$this->dpi, $this->quality, $path, $width, $height]));
        $target = __DIR__.'/../../../var/cache/'.$hash.'.png';
        if (file_exists($target)) {
            return $target;
        }

        $width = (int) ($width * $this->dpi * self::DPI_PIXEL_SCALE_FACTOR);
        $height = (int) ($height * $this->dpi * self::DPI_PIXEL_SCALE_FACTOR);

        // curl -X POST "http://localhost:8000/api/v1/detection/detect" -H "Content-Type: multipart/form-data" -H "x-api-key: f08f6993-bd4a-46c0-b27d-72755816bdc6" -F "file=@horizontal.jpg"
        $center = null;
        if (getenv('COMPREFACE')) {
            $scaledDownTarget = __DIR__.'/../../../var/cache/'.$hash.'-facedetect.jpg';
            $imagick = new \Imagick($path);
            $this->autoRotateImage($imagick);
            $imagick->scaleImage(1024, 1024, true);
            $imagick->setImageFormat('jpeg');
            $imagick->writeImage($scaledDownTarget);

            $eol = "\r\n";
            $mime_boundary = md5(time());
            $data = '--'.$mime_boundary.$eol;
            $data .= 'Content-Disposition: form-data; name="file"; filename="image.jpg"'.$eol;
            $data .= 'Content-Type: image/jpeg'.$eol.$eol;
            $data .= file_get_contents($scaledDownTarget).$eol;
            $data .= '--'.$mime_boundary.'--'.$eol.$eol;

            $faces = json_decode(file_get_contents(
                'http://localhost:8000/api/v1/detection/detect',
                false,
                stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-Type: multipart/form-data; boundary=$mime_boundary\r\n".
                            'x-api-key: '.getenv('COMPREFACE')."\r\n",
                        'ignore_errors' => true,
                        'content' => $data,
                    ],
                ])
            ));
            unlink($scaledDownTarget);

            if ($faces && isset($faces->result)) {
                foreach ($faces->result as $face) {
                    $center['x'][] = $face->box->x_min;
                    $center['x'][] = $face->box->x_max;
                    $center['y'][] = $face->box->y_min;
                    $center['y'][] = $face->box->y_max;
                }

                $center['x'] = array_sum($center['x']) / count($center['x']) / $imagick->getImageWidth();
                $center['y'] = array_sum($center['y']) / count($center['y']) / $imagick->getImageHeight();
            }
        }

        if (!$center) {
            $center = ['x' => .5, 'y' => .5];
        }

        $imagick = new \Imagick($path);
        $this->autoRotateImage($imagick);

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
        $hash = hash('sha256', json_encode([$this->dpi, $this->quality, $path, $width, $height, 'Fit']));
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
        $hash = hash('sha256', json_encode([$this->dpi, $this->quality, $path, 'blurred']));
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
