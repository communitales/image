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
 * Class ActionInterface
 */
interface ActionInterface
{

    /**
     * Apply an action to an image
     *
     * @param Image                $image
     * @param array<string, mixed> $options List of options
     *
     * @return bool True if successful, else false.
     */
    public function process(Image $image, array $options = []): bool;

}
