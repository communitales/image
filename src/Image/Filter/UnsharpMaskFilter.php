<?php

/**
 * @copyright   Copyright (c) 2018 - 2020 Communitales GmbH (https://www.communitales.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Communitales\Component\Image\Filter;

use Communitales\Component\Image\Exception\GdException;
use Communitales\Component\Image\Image;
use InvalidArgumentException;
use function abs;
use function imagecolorallocate;
use function imagecolorat;
use function imageconvolution;
use function imagecreatetruecolor;
use function imagedestroy;
use function imagesetpixel;
use function imagesx;
use function imagesy;
use function max;
use function min;
use function round;

/**
 * Unsharp Mask for PHP - version 2.1.1
 *
 * Unsharp mask algorithm by Torstein Hønsi 2003-07.
 * thoensi_at_netcom_dot_no.
 * Please leave this notice.
 *
 * New:
 * - In version 2.1 (February 26 2007) Tom Bishop has done some important speed enhancements.
 * - From version 2 (July 17 2006) the script uses the imageconvolution function in PHP
 * version >= 5.1, which improves the performance considerably.
 *
 *
 * Unsharp masking is a traditional darkroom technique that has proven very suitable for
 * digital imaging. The principle of unsharp masking is to create a blurred copy of the image
 * and compare it to the underlying original. The difference in colour values
 * between the two images is greatest for the pixels near sharp edges. When this
 * difference is subtracted from the original image, the edges will be
 * accentuated.
 *
 * Any suggestions for improvement of the algorithm, expecially regarding the speed
 * and the roundoff errors in the Gaussian blur process, are welcome.
 */
class UnsharpMaskFilter implements FilterInterface
{

    public const OPTION_AMOUNT = 'amount';
    public const OPTION_RADIUS = 'radius';
    public const OPTION_THRESHOLD = 'threshold';

    /**
     * The Amount parameter simply says how much of the effect you want.
     * 100 is 'normal'.
     * (typically 50 - 200)
     *
     * Radius is the radius of the blurring circle of the mask.
     * (typically 0.5 - 1)
     *
     * Threshold is the least difference in colour values that is allowed
     * between the original and the mask. In practice this means that
     * low-contrast areas of the picture are left unrendered whereas edges
     * are treated normally. This is good for pictures of e.g. skin or blue skies.
     * (typically 0 - 5)
     *
     * @param Image                $image
     * @param array<string, mixed> $options amount, radius, threshold
     *
     * @return bool
     * @throws GdException
     */
    public function process(Image $image, array $options = []): bool
    {
        if (!isset($options['amount'], $options['radius'], $options['threshold'])) {
            throw new InvalidArgumentException(
                'Not all required parameters where set. Required: amount, radius, threshold.'
            );
        }
        $amount = (float)$options[self::OPTION_AMOUNT];
        $radius = (float)$options[self::OPTION_RADIUS];
        $threshold = (int)$options[self::OPTION_THRESHOLD];
        $img = $image->getResource();

        // $img is an image that is already created within php using
        // imgcreatetruecolor. No url! $img must be a truecolor image.

        // Attempt to calibrate the parameters to Photoshop:
        if ($amount > 500) {
            $amount = 500;
        }
        $amount *= 0.016;
        if ($radius > 50) {
            $radius = 50;
        }
        $radius *= 2;
        if ($threshold > 255) {
            $threshold = 255;
        }

        $radius = (int)abs(round($radius)); // Only integers make sense.
        if ($radius === 0) {
            return false;
        }

        $w = imagesx($img);
        $h = imagesy($img);

        $imgCanvas = imagecreatetruecolor($w, $h);
        if ($imgCanvas === false) {
            throw new GdException('Error when using imagecreatetruecolor');
        }

        $imgBlur = imagecreatetruecolor($w, $h);
        if ($imgBlur === false) {
            throw new GdException('Error when using imagecreatetruecolor');
        }

        // Gaussian blur matrix:
        //
        //    1    2    1
        //    2    4    2
        //    1    2    1
        //
        //////////////////////////////////////////////////

        $matrix = [
            [1, 2, 1],
            [2, 4, 2],
            [1, 2, 1],
        ];
        imagecopy($imgBlur, $img, 0, 0, 0, 0, $w, $h);
        imageconvolution($imgBlur, $matrix, 16, 0);

        if ($threshold > 0) {
            // Calculate the difference between the blurred pixels and the original
            // and set the pixels
            for ($x = 0; $x < $w - 1; $x++) { // each row
                for ($y = 0; $y < $h; $y++) { // each pixel

                    $rgbOrig = imagecolorat($img, $x, $y);
                    $rOrig = (($rgbOrig >> 16) & 0xFF);
                    $gOrig = (($rgbOrig >> 8) & 0xFF);
                    $bOrig = ($rgbOrig & 0xFF);

                    $rgbBlur = imagecolorat($imgBlur, $x, $y);

                    $rBlur = (($rgbBlur >> 16) & 0xFF);
                    $gBlur = (($rgbBlur >> 8) & 0xFF);
                    $bBlur = ($rgbBlur & 0xFF);

                    // When the masked pixels differ less from the original
                    // than the threshold specifies, they are set to their original value.
                    $rNew = (abs($rOrig - $rBlur) >= $threshold)
                        ? max(0, min(255, ($amount * ($rOrig - $rBlur)) + $rOrig))
                        : $rOrig;
                    $gNew = (abs($gOrig - $gBlur) >= $threshold)
                        ? max(0, min(255, ($amount * ($gOrig - $gBlur)) + $gOrig))
                        : $gOrig;
                    $bNew = (abs($bOrig - $bBlur) >= $threshold)
                        ? max(0, min(255, ($amount * ($bOrig - $bBlur)) + $bOrig))
                        : $bOrig;

                    if (($rOrig !== $rNew) || ($gOrig !== $gNew) || ($bOrig !== $bNew)) {
                        /** @var int|false $pixCol */
                        $pixCol = imagecolorallocate($img, (int)$rNew, (int)$gNew, (int)$bNew);
                        if ($pixCol === false) {
                            return false;
                        }

                        imagesetpixel($img, $x, $y, $pixCol);
                    }
                }
            }
        } else {
            for ($x = 0; $x < $w; $x++) { // each row
                for ($y = 0; $y < $h; $y++) { // each pixel
                    /** @var false|int $rgbOrig */
                    $rgbOrig = imagecolorat($img, $x, $y);
                    if ($rgbOrig === false) {
                        return false;
                    }

                    $rOrig = (($rgbOrig >> 16) & 0xFF);
                    $gOrig = (($rgbOrig >> 8) & 0xFF);
                    $bOrig = ($rgbOrig & 0xFF);

                    /** @var false|int $rgbBlur */
                    $rgbBlur = imagecolorat($imgBlur, $x, $y);
                    if ($rgbBlur === false) {
                        return false;
                    }

                    $rBlur = (($rgbBlur >> 16) & 0xFF);
                    $gBlur = (($rgbBlur >> 8) & 0xFF);
                    $bBlur = ($rgbBlur & 0xFF);

                    $rNew = ($amount * ($rOrig - $rBlur)) + $rOrig;
                    if ($rNew > 255) {
                        $rNew = 255;
                    } elseif ($rNew < 0) {
                        $rNew = 0;
                    }
                    $gNew = ($amount * ($gOrig - $gBlur)) + $gOrig;
                    if ($gNew > 255) {
                        $gNew = 255;
                    } elseif ($gNew < 0) {
                        $gNew = 0;
                    }
                    $bNew = ($amount * ($bOrig - $bBlur)) + $bOrig;
                    if ($bNew > 255) {
                        $bNew = 255;
                    } elseif ($bNew < 0) {
                        $bNew = 0;
                    }
                    $rgbNew = ($rNew << 16) + ($gNew << 8) + $bNew;
                    imagesetpixel($img, $x, $y, (int)$rgbNew);
                }
            }
        }
        imagedestroy($imgCanvas);
        imagedestroy($imgBlur);

        $image->setResource($img);

        return true;
    }
}
