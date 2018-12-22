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
 * Rotate an image
 */
class RotateAction implements ActionInterface
{

    public const OPTION_ANGLE = 'angle';
    public const OPTION_BACKGROUND_COLOR = 'backgroundColor';
    public const OPTION_IGNORE_TRANSPARENT = 'ignoreTransparent';

    /**
     * @param Image $image
     * @param array $options angle -360 to 360
     *                       backgroundColor (default: 0)
     *                       ignoreTransparent (default: false)
     *
     * @return bool True if successful, else false.
     */
    public function process(Image $image, array $options = []): bool
    {
        if (!isset($options[self::OPTION_ANGLE])) {
            throw new InvalidArgumentException('Some options are missing. Mandatory options: angle');
        }
        $angle = $options[self::OPTION_ANGLE];
        $backgroundColor = $options[self::OPTION_BACKGROUND_COLOR] ?? 0;
        $ignoreTransparent = $options[self::OPTION_IGNORE_TRANSPARENT] ?? 0;

        $resource = $image->getResource();
        $resource = \imagerotate($resource, $angle, $backgroundColor, $ignoreTransparent);

        if ($resource === false) {
            return false;
        }

        $image->setResource($resource);

        return true;
    }
}
