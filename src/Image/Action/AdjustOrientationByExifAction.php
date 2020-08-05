<?php

/**
 * @copyright   Copyright (c) 2018 - 2020 Communitales GmbH (https://www.communitales.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Communitales\Component\Image\Action;

use Communitales\Component\Image\Image;

/**
 * Automatically adjust the image rotation based on the exif data
 */
class AdjustOrientationByExifAction implements ActionInterface
{

    private const EXIF_ORIENTATION = 'Orientation';

    // @see http://www.impulseadventure.com/photo/exif-orientation.html
    private const ORIENTATION_NORMAL = 1;
    private const ORIENTATION_180 = 3;
    private const ORIENTATION_90_RIGHT = 6;
    private const ORIENTATION_90_LEFT = 8;

    /**
     * Rotate the image
     *
     * @param Image                $image
     * @param array<string, mixed> $options Unused
     *
     * @return bool True if successful, else false.
     */
    public function process(Image $image, array $options = []): bool
    {
        $exif = $image->getExifData();

        // No exif data, nothing to do :)
        if (!isset($exif[self::EXIF_ORIENTATION])) {
            return false;
        }

        switch ($exif[self::EXIF_ORIENTATION]) {
            case self::ORIENTATION_180:
                $image->addAction(new RotateAction(), [RotateAction::OPTION_ANGLE => 180]);
                break;
            case self::ORIENTATION_90_RIGHT:
                $image->addAction(new RotateAction(), [RotateAction::OPTION_ANGLE => -90]);
                break;
            case self::ORIENTATION_90_LEFT:
                $image->addAction(new RotateAction(), [RotateAction::OPTION_ANGLE => 90]);
                break;

            case self::ORIENTATION_NORMAL:
            default:
                // Some cameras (e.g. Android Samsung devices) may set a orientation of 0
                break;
        }

        return true;
    }

}
