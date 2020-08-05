<?php

/**
 * @copyright   Copyright (c) 2018 - 2020 Communitales GmbH (https://www.communitales.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Communitales\Component\Image\Action;

use Communitales\Component\Image\Exception\GdException;
use Communitales\Component\Image\Image;
use InvalidArgumentException;
use function imagecopy;
use function imagecreatetruecolor;
use function imagefilledrectangle;
use function round;

/**
 * Crop an image to specified size.
 * If the crop size is larger, than the original size, a fill color can be added.
 */
class CropAction implements ActionInterface
{

    public const OPTION_WIDTH = 'width';
    public const OPTION_HEIGHT = 'height';
    public const OPTION_ORIENTATION = 'orientation';
    public const OPTION_COLOR = 'color';

    // Define to crop from with position
    // Positions same like in Photoshop
    //
    //    1    2    3
    //    4    5    6
    //    7    8    9
    public const CROP_FROM_LEFT_TOP = 1;
    public const CROP_FROM_MIDDLE_TOP = 2;
    public const CROP_FROM_RIGHT_TOP = 3;
    public const CROP_FROM_LEFT_MIDDLE = 4;
    public const CROP_FROM_MIDDLE_MIDDLE = 5;
    public const CROP_FROM_RIGHT_MIDDLE = 6;
    public const CROP_FROM_LEFT_BOTTOM = 7;
    public const CROP_FROM_MIDDLE_BOTTOM = 8;
    public const CROP_FROM_RIGHT_BOTTOM = 9;

    /**
     * @param Image                $image
     * @param array<string, mixed> $options
     *
     * @return bool
     * @throws GdException
     */
    public function process(Image $image, array $options = []): bool
    {
        if (!isset($options[self::OPTION_WIDTH], $options[self::OPTION_HEIGHT])) {
            throw new InvalidArgumentException('Some options are missing. Mandatory options: width, height');
        }
        $width = (int)$options[self::OPTION_WIDTH];
        $height = (int)$options[self::OPTION_HEIGHT];
        $orientation = isset($options[self::OPTION_ORIENTATION]) ? (int)$options[self::OPTION_ORIENTATION] : self::CROP_FROM_MIDDLE_MIDDLE;
        if ($orientation < 1 || $orientation > 9) {
            $orientation = self::CROP_FROM_MIDDLE_MIDDLE;
        }
        $resource = $image->getResource();

        // Original size
        $imageWidth = $image->getWidth();
        $imageHeight = $image->getHeight();

        // Target position of the original image
        $targetX = $this->getTargetPositionX($imageWidth, $width, $orientation);
        $targetY = $this->getTargetPositionY($imageHeight, $height, $orientation);

        $cropedResource = imagecreatetruecolor($width, $height);
        if ($cropedResource === false) {
            throw new GdException('Error when using imagecreatetruecolor');
        }

        // If a fill color was added, then fill the new image
        if (isset($options[self::OPTION_COLOR]) && \is_int($options[self::OPTION_COLOR])) {
            imagefilledrectangle($cropedResource, 0, 0, $width, $height, $options[self::OPTION_COLOR]);
        }

        // Crop the image
        imagecopy($cropedResource, $resource, $targetX, $targetY, 0, 0, $imageWidth, $imageHeight);

        $image->setResource($cropedResource);

        return true;
    }

    /**
     * Calculates by the old and the new size, where to place the new image on X axis.
     *
     * @param int $oldWidth
     * @param int $newWidth
     * @param int $orientation
     *
     * @return int
     */
    private function getTargetPositionX(int $oldWidth, int $newWidth, int $orientation): int
    {
        switch ($orientation) {
            case self::CROP_FROM_LEFT_TOP:
            case self::CROP_FROM_LEFT_MIDDLE:
            case self::CROP_FROM_LEFT_BOTTOM:
                $result = 0;
                break;
            case self::CROP_FROM_MIDDLE_TOP:
            case self::CROP_FROM_MIDDLE_MIDDLE:
            case self::CROP_FROM_MIDDLE_BOTTOM:
                $result = (int)round(($newWidth - $oldWidth) / 2);
                break;
            case self::CROP_FROM_RIGHT_TOP:
            case self::CROP_FROM_RIGHT_MIDDLE:
            case self::CROP_FROM_RIGHT_BOTTOM:
                $result = $newWidth - $oldWidth;
                break;
            default:
                $result = 0;
                break;
        }

        return $result;
    }

    /**
     * Calculates by the old and the new size, where to place the new image on Y axis.
     *
     * @param int $oldHeight
     * @param int $newHeight
     * @param int $orientation
     *
     * @return int
     */
    private function getTargetPositionY(int $oldHeight, int $newHeight, int $orientation): int
    {
        return $this->getTargetPositionX($oldHeight, $newHeight, $orientation);
    }

}
