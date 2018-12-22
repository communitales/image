<?php

/**
 * @copyright   Copyright (c) 2018 Communitales GmbH (http://www.communitales.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Communitales\Component\Image;

use Communitales\Component\Image\Action\ActionInterface;
use Communitales\Component\Image\Action\AdjustOrientationByExifAction;
use Communitales\Component\Image\Filter\FilterInterface;
use InvalidArgumentException;
use RuntimeException;

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
     * Create an adapte for a graphics resource.
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
     * @throws InvalidArgumentException
     */
    public static function createFromFilename(string $filename): Image
    {
        $nameSplit = \explode('.', $filename);
        $extension = \array_pop($nameSplit);
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return self::createFromJpeg($filename);
            case 'png':
                return self::createFromPng($filename);
            default:
                throw new InvalidArgumentException(
                    sprintf(
                        'There is no implemented loading function for the extension "%s". Supported types: jpg, jpeg, png.',
                        $extension
                    )
                );
                break;
        }
    }

    /**
     * Create a new class based on a existing PNG file.
     *
     * @param string $filename
     *
     * @throws RuntimeException
     * @return Image
     */
    public static function createFromPng(string $filename): Image
    {
        if (!\file_exists($filename)) {
            throw new  RuntimeException(
                \sprintf('The image was not found or is not readable: "%s"', $filename)
            );
        }

        $resource = \imagecreatefrompng($filename);

        return new self($resource, $filename);
    }

    /**
     * Create a new class based on a existing JPEG file.
     * Also rotates the image based on the exif data.
     *
     * @param string $filename
     *
     * @throws RuntimeException
     * @return Image
     */
    public static function createFromJpeg(string $filename): Image
    {
        if (!\file_exists($filename)) {
            throw new  RuntimeException(
                \sprintf('The image was not found or is not readable: "%s"', $filename)
            );
        }

        $resource = \imagecreatefromjpeg($filename);
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
     */
    public static function createTrueColor(int $width, int $height): Image
    {
        $resource = \imagecreatetruecolor($width, $height);

        return new self($resource);
    }

    /**
     * Create a new color resource
     *
     * @param Image $image
     * @param int   $red
     * @param int   $green
     * @param int   $blue
     * @param int   $alpha 0-127, 0 bedeutet deckend, 127 ist transparent
     *
     * @return int  A color identifier
     */
    public static function allocateColor(
        Image $image,
        int $red,
        int $green,
        int $blue,
        int $alpha = 0
    ): int {
        return \imagecolorallocatealpha($image->getResource(), $red, $green, $blue, $alpha);
    }

    /**
     * Returns the exif data of an image, when existing.
     *
     * @return array
     */
    public function getExifData(): array
    {
        $exif = [];
        if (!empty($this->filename)) {
            $exif = \exif_read_data($this->filename);
        }
        if ($exif === false) {
            $exif = [];
        }

        return $exif;
    }

    /**
     * Apply a filter to the image
     *
     * @param FilterInterface $filter
     * @param array           $options
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
     * @param ActionInterface $action
     * @param array           $options
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
        return \imagejpeg($this->resource, $targetFilename, $quality);
    }

    /**
     * Returns with of the image in pixels
     *
     * @return int
     */
    public function getWidth(): int
    {
        return \imagesx($this->resource);
    }

    /**
     * Returns height of the image in pixels
     *
     * @return int
     */
    public function getHeight(): int
    {
        return \imagesy($this->resource);
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
        \imageinterlace($this->resource, (int)$boolean);

        return $this;
    }

}