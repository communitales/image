<?php

/**
 * @copyright   Copyright (c) 2018 - 2020 Communitales GmbH (https://www.communitales.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Communitales\Component\Image;

use Communitales\Component\Image\Action\ActionInterface;
use Communitales\Component\Image\Action\AdjustOrientationByExifAction;
use Communitales\Component\Image\Exception\GdException;
use Communitales\Component\Image\Exception\ImageCreateException;
use Communitales\Component\Image\Filter\FilterInterface;
use InvalidArgumentException;
use RuntimeException;
use function exif_read_data;
use function file_exists;
use function imagecolorallocatealpha;
use function imagecreatefromjpeg;
use function imagecreatefrompng;
use function imagecreatetruecolor;
use function imageinterlace;
use function imagejpeg;
use function imagepng;
use function imagesavealpha;
use function imagesx;
use function imagesy;
use function sprintf;

/**
 * Represents an image resource.
 *
 * Adapter for the PHP image functionalities.
 */
class Image
{
    /**
     * @var resource
     */
    private $resource;

    /**
     * The original filename from the opened image
     *
     * @var string
     */
    private $filename;

    /**
     * Create an adapter for a graphics resource.
     * See static create functions.
     *
     * @param resource $resource
     * @param string   $filename
     */
    public function __construct($resource, string $filename = '')
    {
        $this->resource = $resource;
        $this->filename = $filename;
    }

    /**
     * Create a new class based on the given filename.
     * Detects creation method based on file extension.
     *
     * @param string $filename
     *
     * @return Image
     * @throws GdException
     * @throws ImageCreateException
     */
    public static function createFromFilename(string $filename): Image
    {
        if (!file_exists($filename)) {
            throw new  RuntimeException(
                sprintf('The image was not found or is not readable: "%s"', $filename)
            );
        }

        $imageType = exif_imagetype($filename);
        if ($imageType === false) {
            throw new GdException('Could not detect image type');
        }

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return self::createFromJpeg($filename);
            case IMAGETYPE_PNG:
                return self::createFromPng($filename);
            default:
                throw new InvalidArgumentException(
                    sprintf(
                        'There is no implemented loading function for the type "%s". Supported types: jpeg, png.',
                        image_type_to_mime_type($imageType)
                    )
                );
        }
    }

    /**
     * Create a new class based on a existing PNG file.
     *
     * @param string $filename
     *
     * @return Image
     * @throws ImageCreateException
     */
    public static function createFromPng(string $filename): Image
    {
        if (!file_exists($filename)) {
            throw new  ImageCreateException(
                sprintf('The image was not found or is not readable: "%s"', $filename)
            );
        }

        $resource = imagecreatefrompng($filename);
        if ($resource === false) {
            throw new ImageCreateException('Error when reading the png image');
        }

        return new self($resource, $filename);
    }

    /**
     * Create a new class based on a existing JPEG file.
     * Also rotates the image based on the exif data.
     *
     * @param string $filename
     *
     * @return Image
     * @throws ImageCreateException
     */
    public static function createFromJpeg(string $filename): Image
    {
        if (!file_exists($filename)) {
            throw new  ImageCreateException(
                sprintf('The image was not found or is not readable: "%s"', $filename)
            );
        }

        $resource = imagecreatefromjpeg($filename);
        if ($resource === false) {
            throw new ImageCreateException('Error when reading the png image');
        }

        $image = new self($resource, $filename);
        $image->addAction(new AdjustOrientationByExifAction());

        return $image;
    }

    /**
     * Create a new empty image
     *
     * @param int $width
     * @param int $height
     *
     * @return Image
     * @throws GdException
     */
    public static function createTrueColor(int $width, int $height): Image
    {
        $resource = imagecreatetruecolor($width, $height);
        if ($resource === false) {
            throw new GdException('Error when using imagecreatetruecolor');
        }

        return new self($resource);
    }

    /**
     * Create a new color resource
     *
     * @param Image $image
     * @param int   $red
     * @param int   $green
     * @param int   $blue
     * @param int   $alpha A value between 0 and 127. 0 indicates completely opaque while 127 indicates completely
     *                     transparent.
     *
     * @return int|false A color identifier or false if the allocation failed.
     */
    public static function allocateColor(
        Image $image,
        int $red,
        int $green,
        int $blue,
        int $alpha = 0
    ) {
        /** @var int|false $result */
        $result = imagecolorallocatealpha($image->getResource(), $red, $green, $blue, $alpha);

        return $result;
    }

    /**
     * Returns the exif data of an image, when existing.
     *
     * @return array<array-key, mixed>
     */
    public function getExifData(): array
    {
        $exif = [];
        if (!empty($this->filename)) {
            // Suppress warning
            // See: PHP Bug #78083 exif_read_data() corrupt EXIF header: maximum directory nesting level reached
            $exif = @exif_read_data($this->filename);
        }
        if ($exif === false) {
            $exif = [];
        }

        return $exif;
    }

    /**
     * Apply a filter to the image
     *
     * @param FilterInterface      $filter
     * @param array<string, mixed> $options
     *
     * @return bool
     */
    public function addFilter(FilterInterface $filter, array $options = []): bool
    {
        return $filter->process($this, $options);
    }

    /**
     * Process an action on the image
     *
     * @param ActionInterface      $action
     * @param array<string, mixed> $options
     *
     * @return bool
     */
    public function addAction(ActionInterface $action, array $options = []): bool
    {
        return $action->process($this, $options);
    }

    /**
     * Saves the image resource as JPEG
     *
     * @param string $targetFilename
     * @param int    $quality Quality between 0 and 100
     *
     * @return bool
     */
    public function saveAsJpeg(string $targetFilename, int $quality = 98): bool
    {
        return imagejpeg($this->resource, $targetFilename, $quality);
    }

    /**
     * Saves the image resource as PNG
     *
     * @param string $targetFilename
     * @param int    $compression Quality between 0 and 9. -1 uses the zlib compression default, which is 6.
     * @param bool   $saveAlpha   Keep full alpha transparency while saving
     *
     * @return bool
     */
    public function saveAsPng(string $targetFilename, int $compression = 9, bool $saveAlpha = false): bool
    {
        if ($saveAlpha) {
            imagesavealpha($this->resource, true);
        }

        return imagepng($this->resource, $targetFilename, $compression);
    }

    /**
     * Returns with of the image in pixels
     *
     * @return int
     */
    public function getWidth(): int
    {
        return imagesx($this->resource);
    }

    /**
     * Returns height of the image in pixels
     *
     * @return int
     */
    public function getHeight(): int
    {
        return imagesy($this->resource);
    }

    /**
     * @param resource $resource
     *
     * @return Image
     */
    public function setResource($resource): Image
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * Return the internal image resource
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Enables interlace. When saving as JPEG, the image will be saved as progressive JPEG.
     *
     * @param bool $boolean
     *
     * @return Image
     */
    public function setInterlace(bool $boolean): Image
    {
        imageinterlace($this->resource, (int)$boolean);

        return $this;
    }

}
