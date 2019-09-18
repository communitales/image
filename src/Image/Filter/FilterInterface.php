<?php

/**
 * @copyright   Copyright (c) 2018 Communitales GmbH (https://www.communitales.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Communitales\Component\Image\Filter;

use Communitales\Component\Image\Image;

/**
 * Class FilterInterface
 */
interface FilterInterface
{

    /**
     * Apply a filter to an image
     *
     * @param Image $image
     * @param array $options List of options
     *
     * @return bool True if successful, else false.
     */
    public function process(Image $image, array $options = []): bool;

}
