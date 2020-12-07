<?php

/**
 * @copyright   Copyright (c) 2020 Communitales GmbH (https://www.communitales.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Communitales\Component\Image\Exception\GdException;
use Communitales\Component\Image\Image;
use PHPUnit\Framework\TestCase;

/**
 * Class ImageTest
 */
class ImageTest extends TestCase
{

    /**
     * @throws GdException
     */
    public function testCreateTrueColor(): void
    {
        $image = Image::createTrueColor(10, 10);

        self::assertInstanceOf(Image::class, $image);
    }
}
