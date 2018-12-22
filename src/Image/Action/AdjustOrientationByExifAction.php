<?php

/**
 * @copyright   Copyright (c) 2018 Communitales GmbH (http://www.communitales.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Communitales\Component\Image\Action;

use Communitales\Component\Image\Image;
use InvalidArgumentException;

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
     * @param Image $image
     * @param array $options Not used
     *
     * @return bool True if successful, else false.
     */
    public function process(Image $image, array $options = []): bool
    {
        $exif = $image->getExifData();

        // No exif data, nothing work :)
        if (!isset($exif[self::EXIF_ORIENTATION])) {
            return false;
        }

        switch ($exif[self::EXIF_ORIENTATION]) {
            case self::ORIENTATION_NORMAL:
                break;
            case self::ORIENTATION_180:
                $image->addAction(new RotateAction(), [RotateAction::OPTION_ANGLE => 180]);
                break;
            case self::ORIENTATION_90_RIGHT:
                $image->addAction(new RotateAction(), [RotateAction::OPTION_ANGLE => -90]);
                break;
            case self::ORIENTATION_90_LEFT:
                $image->addAction(new RotateAction(), [RotateAction::OPTION_ANGLE => 90]);
                break;

            default:
                throw new InvalidArgumentException(
                    'Not supported orientation found: '.$exif[self::EXIF_ORIENTATION]
                );
                break;
        }

        return true;
    }

}
