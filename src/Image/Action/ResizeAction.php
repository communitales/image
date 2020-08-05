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
use function imagecopyresampled;
use function imagecreatetruecolor;
use function min;
use function round;

/**
 * Resize the image to a max size
 */
class ResizeAction implements ActionInterface
{

    public const OPTION_WIDTH = 'width';
    public const OPTION_HEIGHT = 'height';
    public const OPTION_KEEP_ASPECT_RATIO = 'keepAspectRatio';

    /**
     * @param Image                $image   Bildresource
     * @param array<string, mixed> $options width
     *                                      height
     *                                      keepAspectRatio (default: true)
     *
     * @return bool Sagt ob die Action erfolgreich angewendet wurde
     * @throws GdException
     */
    public function process(Image $image, array $options = []): bool
    {
        if (!isset($options[self::OPTION_WIDTH], $options[self::OPTION_HEIGHT])) {
            throw new InvalidArgumentException('Some options are missing. Mandatory options: width, height');
        }
        $width = (int)$options[self::OPTION_WIDTH];
        $height = (int)$options[self::OPTION_HEIGHT];
        $keepAspectRatio = isset($options[self::OPTION_KEEP_ASPECT_RATIO]) ? (bool)$options[self::OPTION_KEEP_ASPECT_RATIO] : true;
        $resource = $image->getResource();

        // Does not make sense
        if ($width === 0 || $height === 0) {
            return false;
        }

        $imageWidth = $image->getWidth();
        $imageHeight = $image->getHeight();

        // Calculate real target size
        if ($keepAspectRatio) {
            $factor = min($width / $imageWidth, $height / $imageHeight);
            $width = (int)round($imageWidth * $factor);
            $height = (int)round($imageHeight * $factor);
        }

        // If the image already has the desired size, we are done.
        if ($width === $imageWidth && $height === $imageHeight) {
            return true;
        }

        // Now resize to the new image
        $resizedResource = imagecreatetruecolor($width, $height);
        if ($resizedResource === false) {
            throw new GdException('Error when using imagecreatetruecolor');
        }
        imagecopyresampled($resizedResource, $resource, 0, 0, 0, 0, $width, $height, $imageWidth, $imageHeight);

        $image->setResource($resizedResource);

        return true;
    }
}
