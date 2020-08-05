<?php

/**
 * @copyright   Copyright (c) 2019 - 2020 Communitales GmbH (https://www.communitales.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Communitales\Component\Image\Action;

use Communitales\Component\Image\Exception\GdException;
use Communitales\Component\Image\Image;
use InvalidArgumentException;
use function imagecopy;
use function imagecopymerge;
use function imagecreatetruecolor;
use function min;

/**
 * Copy one image to another
 */
class CopyAction implements ActionInterface
{

    public const OPTION_SOURCE_IMAGE = 'sourceImage';
    public const OPTION_TARGET_X = 'targetX';
    public const OPTION_TARGET_Y = 'targetY';
    public const OPTION_OPACITY = 'opacity'; // 0 - 100, 0 = none, 100 = full

    /**
     * @param Image                $image
     * @param array<string, mixed> $options
     *
     * @return bool
     * @throws GdException
     */
    public function process(Image $image, array $options = []): bool
    {
        if (!isset($options[self::OPTION_TARGET_X], $options[self::OPTION_TARGET_Y], $options[self::OPTION_SOURCE_IMAGE])) {
            throw new InvalidArgumentException('Some options are missing');
        }
        if (!($options[self::OPTION_SOURCE_IMAGE] instanceof Image)) {
            throw new InvalidArgumentException('Source image must be of class '.Image::class);
        }

        $sourceImage = $options[self::OPTION_SOURCE_IMAGE];
        $targetX = (int)$options[self::OPTION_TARGET_X];
        $targetY = (int)$options[self::OPTION_TARGET_Y];
        $opacity = (int)($options[self::OPTION_OPACITY] ?? 100);
        $opacity = min(100, max(0, $opacity));

        return $this->imageCopyMergeAlpha(
            $image->getResource(),
            $sourceImage->getResource(),
            $targetX,
            $targetY,
            0,
            0,
            $sourceImage->getWidth(),
            $sourceImage->getHeight(),
            $opacity
        );
    }

    /**
     * PNG ALPHA CHANNEL SUPPORT for imagecopymerge();
     * by Sina Salek
     *
     * Bugfix by Ralph Voigt (bug which causes it
     * to work only for $src_x = $src_y = 0.
     * Also, inverting opacity is not necessary.)
     * 08-JAN-2011
     *
     * @param resource $destinationImage
     * @param resource $sourceImage
     * @param int      $destinationX
     * @param int      $destinationY
     * @param int      $sourceX
     * @param int      $sourceY
     * @param int      $sourceWidth
     * @param int      $sourceHeight
     * @param int      $percent
     *
     * @return bool
     * @throws GdException
     */
    private function imageCopyMergeAlpha(
        $destinationImage,
        $sourceImage,
        int $destinationX,
        int $destinationY,
        int $sourceX,
        int $sourceY,
        int $sourceWidth,
        int $sourceHeight,
        int $percent
    ): bool {
        // creating a cut resource
        $cut = imagecreatetruecolor($sourceWidth, $sourceHeight);
        if ($cut === false) {
            throw new GdException('Error when using imagecreatetruecolor');
        }

        // copying relevant section from background to the cut resource
        imagecopy($cut, $destinationImage, 0, 0, $destinationX, $destinationY, $sourceWidth, $sourceHeight);

        // copying relevant section from watermark to the cut resource
        imagecopy($cut, $sourceImage, 0, 0, $sourceX, $sourceY, $sourceWidth, $sourceHeight);

        // insert cut resource to destination image
        return imagecopymerge(
            $destinationImage,
            $cut,
            $destinationX,
            $destinationY,
            0,
            0,
            $sourceWidth,
            $sourceHeight,
            $percent
        );
    }
}
