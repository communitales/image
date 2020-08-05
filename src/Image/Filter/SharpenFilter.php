<?php

/**
 * @copyright   Copyright (c) 2018 - 2020 Communitales GmbH (https://www.communitales.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Communitales\Component\Image\Filter;

use Communitales\Component\Image\Image;
use function array_map;
use function array_sum;
use function imageconvolution;

/**
 * Sharpen the image
 */
class SharpenFilter implements FilterInterface
{

    public const OPTION_TYPE = 'type';

    public const TYPE_NORMAL = 0;
    public const TYPE_SMOOTH = 1;

    /**
     * @var array<array-key, array<array-key, float[]>>
     */
    private $sharpenMatrix = [
        [
            [0.0, -1.0, 0.0],
            [-1.0, 5.0, -1.0],
            [0.0, -1.0, 0.0],
        ],
        [
            [-1.0, -1.0, -1.0],
            [-1.0, 16.0, -1.0],
            [-1.0, -1.0, -1.0],
        ],
    ];

    /**
     * @param Image                $image
     * @param array<string, mixed> $options List of options [type = {NORMAL,SMOOTH}]
     *
     * @return bool
     */
    public function process(Image $image, array $options = []): bool
    {
        $imageResource = $image->getResource();
        $type = $options[self::OPTION_TYPE] === 1 ? $options[self::OPTION_TYPE] : self::TYPE_SMOOTH;
        $sharpenMatrix = $this->sharpenMatrix[$type];
        $offset = 0;

        // calculate the sharpen divisor
        $divisor = array_sum(array_map('array_sum', $sharpenMatrix));

        // apply the matrix
        imageconvolution($imageResource, $sharpenMatrix, $divisor, $offset);

        return true;
    }
}
