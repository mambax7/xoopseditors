<?php
# ========================================================================#
#
#  This work is licensed under the Creative Commons Attribution 3.0 Unported
#  License. To view a copy of this license,
#  visit http://creativecommons.org/licenses/by/3.0/ or send a letter to
#  Creative Commons, 444 Castro Street, Suite 900, Mountain View, California,
#  94041, USA.
#
#  All rights reserved.
#
#  Author:    Jarrod Oberto
#  Version:   1.5.2
#  Purpose:   Provide tools for image manipulation using GD
#  Param In:  See functions.
#  Param Out: Produces a resized image
#  Requires : Requires PHP GD library.
#  Usage Example:
#                     include("lib/php_image_magician.php");
#                     $magicianObj = new imageLib('images/car.jpg');
#                     $magicianObj -> resizeImage(150, 100, 0);
#                     $magicianObj -> saveImage('images/car_small.jpg', 100);
#
#        - See end of doc for more examples -
#
#  Supported file types include: jpg, png, gif, bmp, psd (read)
#
#
#
#  The following functions are taken from phpThumb() [available from
#    http://phpthumb.sourceforge.net], and are used with written permission
#  from James Heinrich.
#    - GD2BMPstring
#      - GetPixelColor
#      - LittleEndian2String
#
#  The following functions are from Marc Hibbins and are used with written
#  permission (are also under the Attribution-ShareAlike
#  [http://creativecommons.org/licenses/by-sa/3.0/] license.
#    -
#
#  PhpPsdReader is used with written permission from Tim de Koning.
#  [http://www.kingsquare.nl/phppsdreader]
#
#
#
#  Known issues & Limitations:
# -------------------------------
#  Not so much an issue, the image is destroyed on the deconstruct rather than
#  when we have finished with it. The reason for this is that we don't know
#  when we're finished with it as you can both save the image and display
#  it directly to the screen (imagedestroy($this->imageResized))
#
#  Opening BMP files is slow. A test with 884 bmp files processed in a loop
#  takes forever - over 5 min. This test inlcuded opening the file, then
#  getting and displaying its width and height.
#
#  $forceStretch:
# -------------------------------
#  On by default.
#  $forceStretch can be disabled by calling method setForceStretch with false
#  parameter. If disabled, if an images original size is smaller than the size
#  specified by the user, the original size will be used. This is useful when
#  dealing with small images.
#
#  If enabled, images smaller than the size specified will be stretched to
#  that size.
#
#  Tips:
# -------------------------------
#  * If you're resizing a transparent png and saving it as a jpg, set
#  $keepTransparency to false with: $magicianObj->setTransparency(false);
#
#  FEATURES:
#    * EASY TO USE
#    * BMP SUPPORT (read & write)
#    * PSD (photoshop) support (read)
#    * RESIZE IMAGES
#      - Preserve transparency (png, gif)
#      - Apply sharpening (jpg) (requires PHP >= 5.1.0)
#      - Set image quality (jpg, png)
#      - Resize modes:
#        - exact size
#        - resize by width (auto height)
#        - resize by height (auto width)
#        - auto (automatically determine the best of the above modes to use)
#        - crop - resize as best as it can then crop the rest
#      - Force stretching of smaller images (upscale)
#    * APPLY FILTERS
#      - Convert to grey scale
#      - Convert to black and white
#      - Convert to sepia
#      - Convert to negative
#    * ROTATE IMAGES
#      - Rotate using predefined "left", "right", or "180"; or any custom degree amount
#    * EXTRACT EXIF DATA (requires exif module)
#      - make
#      - model
#      - date
#      - exposure
#      - aperture
#      - f-stop
#      - iso
#      - focal length
#      - exposure program
#      - metering mode
#      - flash status
#      - creator
#      - copyright
#    * ADD WATERMARK
#      - Specify exact x, y placement
#      - Or, specify using one of the 9 pre-defined placements such as "tl"
#        (for top left), "m" (for middle), "br" (for bottom right)
#        - also specify padding from edge amount (optional).
#      - Set opacity of watermark (png).
#    * ADD BORDER
#    * USE HEX WHEN SPECIFYING COLORS (eg: #ffffff)
#    * SAVE IMAGE OR OUTPUT TO SCREEN
#
#
# ========================================================================#

/**
 * Class imageLib
 */
class imageLib
{
    private $fileName;
    private $image;
    protected $imageResized;
    private $widthOriginal;     # Always be the original width
    private $heightOriginal;
    private $width;         # Current width (width after resize)
    private $height;
    private $imageSize;
    private $fileExtension;

    private $isImage = false;

    private $debug = true;
    private $errorArray = [];

    private $forceStretch = true;
    private $aggresiveSharpening = false;

    private $transparentArray = ['.png', '.gif'];
    private $keepTransparency = true;
    private $fillColorArray = ['r' => 255, 'g' => 255, 'b' => 255];

    private $sharpenArray = ['jpg'];

    private $psdReaderPath;
    private $filterOverlayPath;

    private $isInterlace;

    private $captionBoxPositionArray = [];

    private $fontDir = 'fonts';

    private $cropFromTopPercent = 10;

    ## --------------------------------------------------------

    /**
     * imageLib constructor.
     * @param $fileName
     */
    public function __construct($fileName)
    {
        if (!$this->testGDInstalled()) {
            if ($this->debug) {
                die('The GD Library is not installed.');
            }
            die();
        }

        $this->initialise();

        // *** Save the image file name. Only store this incase you want to display it
        $this->fileName = $fileName;
        $this->fileExtension = mb_strtolower(mb_strrchr($fileName, '.'));

        // *** Open up the file
        try {
            $this->image = $this->openImage($fileName);
        } catch (Exception $e) {
        }

        // *** Assign here so we don't modify the original
        $this->imageResized = $this->image;

        // *** If file is an image
        $this->isImage = $this->testIsImage();

        if ($this->isImage) {
            // *** Get width and height
            $this->width = imagesx($this->image);
            $this->widthOriginal = imagesx($this->image);
            $this->height = imagesy($this->image);
            $this->heightOriginal = imagesy($this->image);

            /*  Added 15-09-08
             *  Get the filesize using this build in method.
             *  Stores an array of size
             *
             *  $this->imageSize[1] = width
             *  $this->imageSize[2] = height
             *  $this->imageSize[3] = width x height
             *
             */
            $this->imageSize = getimagesize($this->fileName);
        } else {
            $this->errorArray[] = 'File is not an image';
        }
    }

    ## --------------------------------------------------------

    private function initialise()
    {
        $this->psdReaderPath = __DIR__ . '/classPhpPsdReader.php';
        $this->filterOverlayPath = __DIR__ . '/filters';

        // *** Set if image should be interlaced or not.
        $this->isInterlace = false;
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Resize
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param      $newWidth
     * @param      $newHeight
     * @param int  $option
     * @param bool $sharpen
     * @param bool $autoRotate
     */
    public function resizeImage($newWidth, $newHeight, $option = 0, $sharpen = false, $autoRotate = true)
    {
        // *** We can pass in an array of options to change the crop position
        $cropPos = 'm';
        if (is_array($option) && 'crop' === mb_strtolower($option[0])) {
            $cropPos = $option[1];         # get the crop option
        } elseif (false !== mb_strpos($option, '-')) {
            // *** Or pass in a hyphen seperated option
            $optionPiecesArray = explode('-', $option);
            $cropPos = end($optionPiecesArray);
        }

        // *** Check the option is valid
        try {
            $option = $this->prepOption($option);
        } catch (Exception $e) {
        }

        // *** Make sure the file passed in is valid
        if (!$this->image) {
            if ($this->debug) {
                die('file ' . $this->getFileName() . ' is missing or invalid');
            }
            die();
        }

        // *** Get optimal width and height - based on $option
        $dimensionsArray = $this->getDimensions($newWidth, $newHeight, $option);

        $optimalWidth = $dimensionsArray['optimalWidth'];
        $optimalHeight = $dimensionsArray['optimalHeight'];

        // *** Resample - create image canvas of x, y size
        $this->imageResized = imagecreatetruecolor($optimalWidth, $optimalHeight);
        $this->keepTransparancy($optimalWidth, $optimalHeight, $this->imageResized);
        imagecopyresampled($this->imageResized, $this->image, 0, 0, 0, 0, $optimalWidth, $optimalHeight, $this->width, $this->height);

        // *** If '4', then crop too
        if (4 == $option || 'crop' === $option) {
            if (($optimalWidth >= $newWidth && $optimalHeight >= $newHeight)) {
                $this->crop($optimalWidth, $optimalHeight, $newWidth, $newHeight, $cropPos);
            }
        }

        // *** If Rotate.
        if ($autoRotate) {
            try {
                $exifData = $this->getExif(false);
            } catch (Exception $e) {
            }
            if (count($exifData) > 0) {
                switch ($exifData['orientation']) {
                    case 8:
                        $this->imageResized = imagerotate($this->imageResized, 90, 0);
                        break;
                    case 3:
                        $this->imageResized = imagerotate($this->imageResized, 180, 0);
                        break;
                    case 6:
                        $this->imageResized = imagerotate($this->imageResized, -90, 0);
                        break;
                }
            }
        }

        // *** Sharpen image (if jpg and the user wishes to do so)
        if ($sharpen && in_array($this->fileExtension, $this->sharpenArray)) {
            // *** Sharpen
            try {
                $this->sharpen();
            } catch (Exception $e) {
            }
        }
    }

    ## --------------------------------------------------------

    /**
     * @param        $newWidth
     * @param        $newHeight
     * @param string $cropPos
     */
    public function cropImage($newWidth, $newHeight, $cropPos = 'm')
    {
        // *** Make sure the file passed in is valid
        if (!$this->image) {
            if ($this->debug) {
                die('file ' . $this->getFileName() . ' is missing or invalid');
            }
            die();
        }

        $this->imageResized = $this->image;
        $this->crop($this->width, $this->height, $newWidth, $newHeight, $cropPos);
    }

    ## --------------------------------------------------------

    /**
     * @param $width
     * @param $height
     * @param $im
     */
    private function keepTransparancy($width, $height, $im)
    {
        // *** If PNG, perform some transparency retention actions (gif untested)
        if (in_array($this->fileExtension, $this->transparentArray) && $this->keepTransparency) {
            imagealphablending($im, false);
            imagesavealpha($im, true);
            $transparent = imagecolorallocatealpha($im, 255, 255, 255, 127);
            imagefilledrectangle($im, 0, 0, $width, $height, $transparent);
        } else {
            $color = imagecolorallocate($im, $this->fillColorArray['r'], $this->fillColorArray['g'], $this->fillColorArray['b']);
            imagefilledrectangle($im, 0, 0, $width, $height, $color);
        }
    }

    ## --------------------------------------------------------

    /**
     * @param $optimalWidth
     * @param $optimalHeight
     * @param $newWidth
     * @param $newHeight
     * @param $cropPos
     */
    private function crop($optimalWidth, $optimalHeight, $newWidth, $newHeight, $cropPos)
    {
        // *** Get cropping co-ordinates
        $cropArray = $this->getCropPlacing($optimalWidth, $optimalHeight, $newWidth, $newHeight, $cropPos);
        $cropStartX = $cropArray['x'];
        $cropStartY = $cropArray['y'];

        // *** Crop this bad boy
        $crop = imagecreatetruecolor($newWidth, $newHeight);
        $this->keepTransparancy($optimalWidth, $optimalHeight, $crop);
        imagecopyresampled($crop, $this->imageResized, 0, 0, $cropStartX, $cropStartY, $newWidth, $newHeight, $newWidth, $newHeight);

        $this->imageResized = $crop;

        // *** Set new width and height to our variables
        $this->width = $newWidth;
        $this->height = $newHeight;
    }

    ## --------------------------------------------------------

    /**
     * @param        $optimalWidth
     * @param        $optimalHeight
     * @param        $newWidth
     * @param        $newHeight
     * @param string $pos
     * @return float[]|int[]
     */
    private function getCropPlacing($optimalWidth, $optimalHeight, $newWidth, $newHeight, $pos = 'm')
    {
        $pos = mb_strtolower($pos);

        // *** If co-ords have been entered
        if (mb_strstr($pos, 'x')) {
            $pos = str_replace(' ', '', $pos);

            $xyArray = explode('x', $pos);
            list($cropStartX, $cropStartY) = $xyArray;
        } else {
            switch ($pos) {
                case 'tl':
                    $cropStartX = 0;
                    $cropStartY = 0;
                    break;
                case 't':
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = 0;
                    break;
                case 'tr':
                    $cropStartX = $optimalWidth - $newWidth;
                    $cropStartY = 0;
                    break;
                case 'l':
                    $cropStartX = 0;
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;
                case 'm':
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;
                case 'r':
                    $cropStartX = $optimalWidth - $newWidth;
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;
                case 'bl':
                    $cropStartX = 0;
                    $cropStartY = $optimalHeight - $newHeight;
                    break;
                case 'b':
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = $optimalHeight - $newHeight;
                    break;
                case 'br':
                    $cropStartX = $optimalWidth - $newWidth;
                    $cropStartY = $optimalHeight - $newHeight;
                    break;
                case 'auto':
                    // *** If image is a portrait crop from top, not center. v1.5
                    if ($optimalHeight > $optimalWidth) {
                        $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                        $cropStartY = ($this->cropFromTopPercent / 100) * $optimalHeight;
                    } else {
                        // *** Else crop from the center
                        $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                        $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    }
                    break;
                default:
                    // *** Default to center
                    $cropStartX = ($optimalWidth / 2) - ($newWidth / 2);
                    $cropStartY = ($optimalHeight / 2) - ($newHeight / 2);
                    break;
            }
        }

        return ['x' => $cropStartX, 'y' => $cropStartY];
    }

    ## --------------------------------------------------------

    /**
     * @param $newWidth
     * @param $newHeight
     * @param $option
     * @return array
     */
    private function getDimensions($newWidth, $newHeight, $option)
    {
        switch ((string)$option) {
            case '0':
            case 'exact':
                $optimalWidth = $newWidth;
                $optimalHeight = $newHeight;
                break;
            case '1':
            case 'portrait':
                $dimensionsArray = $this->getSizeByFixedHeight($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
                break;
            case '2':
            case 'landscape':
                $dimensionsArray = $this->getSizeByFixedWidth($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
                break;
            case '3':
            case 'auto':
                $dimensionsArray = $this->getSizeByAuto($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
                break;
            case '4':
            case 'crop':
                $dimensionsArray = $this->getOptimalCrop($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
                break;
        }

        return ['optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight];
    }

    ## --------------------------------------------------------

    /**
     * @param $newWidth
     * @param $newHeight
     * @return array
     */
    private function getSizeByFixedHeight($newWidth, $newHeight)
    {
        // *** If forcing is off...
        if (!$this->forceStretch) {
            // *** ...check if actual height is less than target height
            if ($this->height < $newHeight) {
                return ['optimalWidth' => $this->width, 'optimalHeight' => $this->height];
            }
        }

        $ratio = $this->width / $this->height;

        $newWidth = $newHeight * $ratio;

        //return $newWidth;
        return ['optimalWidth' => $newWidth, 'optimalHeight' => $newHeight];
    }

    ## --------------------------------------------------------

    /**
     * @param $newWidth
     * @param $newHeight
     * @return array
     */
    private function getSizeByFixedWidth($newWidth, $newHeight)
    {
        // *** If forcing is off...
        if (!$this->forceStretch) {
            // *** ...check if actual width is less than target width
            if ($this->width < $newWidth) {
                return ['optimalWidth' => $this->width, 'optimalHeight' => $this->height];
            }
        }

        $ratio = $this->height / $this->width;

        $newHeight = $newWidth * $ratio;

        //return $newHeight;
        return ['optimalWidth' => $newWidth, 'optimalHeight' => $newHeight];
    }

    ## --------------------------------------------------------

    /**
     * @param $newWidth
     * @param $newHeight
     * @return array
     */
    private function getSizeByAuto($newWidth, $newHeight)
    {
        // *** If forcing is off...
        if (!$this->forceStretch) {
            // *** ...check if actual size is less than target size
            if ($this->width < $newWidth && $this->height < $newHeight) {
                return ['optimalWidth' => $this->width, 'optimalHeight' => $this->height];
            }
        }

        if ($this->height < $this->width) { // *** Image to be resized is wider (landscape)
            //$optimalWidth = $newWidth;
            //$optimalHeight= $this->getSizeByFixedWidth($newWidth);

            $dimensionsArray = $this->getSizeByFixedWidth($newWidth, $newHeight);
            $optimalWidth = $dimensionsArray['optimalWidth'];
            $optimalHeight = $dimensionsArray['optimalHeight'];
        } elseif ($this->height > $this->width) { // *** Image to be resized is taller (portrait)
            //$optimalWidth = $this->getSizeByFixedHeight($newHeight);
            //$optimalHeight= $newHeight;

            $dimensionsArray = $this->getSizeByFixedHeight($newWidth, $newHeight);
            $optimalWidth = $dimensionsArray['optimalWidth'];
            $optimalHeight = $dimensionsArray['optimalHeight'];
        } else { // *** Image to be resizerd is a square
            if ($newHeight < $newWidth) {
                //$optimalWidth = $newWidth;
                //$optimalHeight= $this->getSizeByFixedWidth($newWidth);
                $dimensionsArray = $this->getSizeByFixedWidth($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
            } elseif ($newHeight > $newWidth) {
                //$optimalWidth = $this->getSizeByFixedHeight($newHeight);
                //$optimalHeight= $newHeight;
                $dimensionsArray = $this->getSizeByFixedHeight($newWidth, $newHeight);
                $optimalWidth = $dimensionsArray['optimalWidth'];
                $optimalHeight = $dimensionsArray['optimalHeight'];
            } else {
                // *** Sqaure being resized to a square
                $optimalWidth = $newWidth;
                $optimalHeight = $newHeight;
            }
        }

        return ['optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight];
    }

    ## --------------------------------------------------------

    /**
     * @param $newWidth
     * @param $newHeight
     * @return array
     */
    private function getOptimalCrop($newWidth, $newHeight)
    {
        // *** If forcing is off...
        if (!$this->forceStretch) {
            // *** ...check if actual size is less than target size
            if ($this->width < $newWidth && $this->height < $newHeight) {
                return ['optimalWidth' => $this->width, 'optimalHeight' => $this->height];
            }
        }

        $heightRatio = $this->height / $newHeight;
        $widthRatio = $this->width / $newWidth;

        if ($heightRatio < $widthRatio) {
            $optimalRatio = $heightRatio;
        } else {
            $optimalRatio = $widthRatio;
        }

        $optimalHeight = round($this->height / $optimalRatio);
        $optimalWidth = round($this->width / $optimalRatio);

        return ['optimalWidth' => $optimalWidth, 'optimalHeight' => $optimalHeight];
    }

    ## --------------------------------------------------------

    private function sharpen()
    {
        if (version_compare(PHP_VERSION, '5.1.0') >= 0) {
            // ***
            if ($this->aggresiveSharpening) { # A more aggressive sharpening solution
                $sharpenMatrix = [
                    [-1, -1, -1],
                    [-1, 16, -1],
                    [-1, -1, -1],
                ];
                $divisor = 8;
                $offset = 0;

                imageconvolution($this->imageResized, $sharpenMatrix, $divisor, $offset);
            } else { # More subtle and personally more desirable
                $sharpness = $this->findSharp($this->widthOriginal, $this->width);

                $sharpenMatrix = [
                    [-1, -2, -1],
                    [-2, $sharpness + 12, -2], //Lessen the effect of a filter by increasing the value in the center cell
                    [-1, -2, -1],
                ];
                $divisor = $sharpness; // adjusts brightness
                $offset = 0;
                imageconvolution($this->imageResized, $sharpenMatrix, $divisor, $offset);
            }
        } else {
            if ($this->debug) {
                die('Sharpening required PHP 5.1.0 or greater.');
            }
        }
    }

    ## --------------------------------------------------------

    /**
     * @param $level
     */
    private function sharpen2($level)
    {
        $sharpenMatrix = [
            [$level, $level, $level],
            [$level, (8 * $level) + 1, $level], //Lessen the effect of a filter by increasing the value in the center cell
            [$level, $level, $level],
        ];
    }

    ## --------------------------------------------------------

    /**
     * @param $orig
     * @param $final
     * @return mixed
     */
    private function findSharp($orig, $final)
    {
        $final = $final * (750.0 / $orig);
        $a = 52;
        $b = -0.27810650887573124;
        $c = .00047337278106508946;

        $result = $a + $b * $final + $c * $final * $final;

        return max(round($result), 0);
    }

    ## --------------------------------------------------------

    /**
     * @param $option
     * @return bool|false|string|string[]|null
     */
    private function prepOption($option)
    {
        if (is_array($option)) {
            if ('crop' === mb_strtolower($option[0]) && 2 == count($option)) {
                return 'crop';
            }
            die('Crop resize option array is badly formatted.');
        } elseif (false !== mb_strpos($option, 'crop')) {
            return 'crop';
        }

        if (is_string($option)) {
            return mb_strtolower($option);
        }

        return $option;
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Presets
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    #
    # Preset are pre-defined templates you can apply to your image.
    #
    # These are inteded to be applied to thumbnail images.
    #

    /**
     * @param $preset
     */
    public function borderPreset($preset)
    {
        switch ($preset) {
            case 'simple':
                $this->addBorder(7, '#fff');
                $this->addBorder(6, '#f2f1f0');
                $this->addBorder(2, '#fff');
                $this->addBorder(1, '#ccc');
                break;
            default:
                break;
        }
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Draw border
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param int   $thickness
     * @param int[] $rgbArray
     */
    public function addBorder($thickness = 1, $rgbArray = [255, 255, 255])
    {
        if ($this->imageResized) {
            $rgbArray = $this->formatColor($rgbArray);
            $r = $rgbArray['r'];
            $g = $rgbArray['g'];
            $b = $rgbArray['b'];

            $x1 = 0;
            $y1 = 0;
            $x2 = imagesx($this->imageResized) - 1;
            $y2 = imagesy($this->imageResized) - 1;

            $rgbArray = imagecolorallocate($this->imageResized, $r, $g, $b);

            for ($i = 0; $i < $thickness; ++$i) {
                imagerectangle($this->imageResized, ++$x1, ++$y1, $x2--, $y2--, $rgbArray);
            }
        }
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Gray Scale
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    public function greyScale()
    {
        if ($this->imageResized) {
            imagefilter($this->imageResized, IMG_FILTER_GRAYSCALE);
        }
    }

    ## --------------------------------------------------------

    public function greyScaleEnhanced()
    {
        if ($this->imageResized) {
            imagefilter($this->imageResized, IMG_FILTER_GRAYSCALE);
            imagefilter($this->imageResized, IMG_FILTER_CONTRAST, -15);
            imagefilter($this->imageResized, IMG_FILTER_BRIGHTNESS, 2);

            try {
                $this->sharpen($this->width);
            } catch (Exception $e) {
            }
        }
    }

    ## --------------------------------------------------------

    public function greyScaleDramatic()
    {
        $this->gd_filter_monopin();
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Black 'n White
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    public function blackAndWhite()
    {
        if ($this->imageResized) {
            imagefilter($this->imageResized, IMG_FILTER_GRAYSCALE);
            imagefilter($this->imageResized, IMG_FILTER_CONTRAST, -1000);
        }
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Negative
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    public function negative()
    {
        if ($this->imageResized) {
            imagefilter($this->imageResized, IMG_FILTER_NEGATE);
        }
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Sepia
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    public function sepia()
    {
        if ($this->imageResized) {
            imagefilter($this->imageResized, IMG_FILTER_GRAYSCALE);
            imagefilter($this->imageResized, IMG_FILTER_BRIGHTNESS, -10);
            imagefilter($this->imageResized, IMG_FILTER_CONTRAST, -20);
            imagefilter($this->imageResized, IMG_FILTER_COLORIZE, 60, 30, -15);
        }
    }

    ## --------------------------------------------------------

    public function sepia2()
    {
        if ($this->imageResized) {
            $total = imagecolorstotal($this->imageResized);
            for ($i = 0; $i < $total; ++$i) {
                $index = imagecolorsforindex($this->imageResized, $i);
                $red = ($index['red'] * 0.393 + $index['green'] * 0.769 + $index['blue'] * 0.189) / 1.351;
                $green = ($index['red'] * 0.349 + $index['green'] * 0.686 + $index['blue'] * 0.168) / 1.203;
                $blue = ($index['red'] * 0.272 + $index['green'] * 0.534 + $index['blue'] * 0.131) / 2.140;
                imagecolorset($this->imageResized, $i, $red, $green, $blue);
            }
        }
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Vintage
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    public function vintage()
    {
        $this->gd_filter_vintage();
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Presets By Marc Hibbins
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /** Apply 'Monopin' preset */
    public function gd_filter_monopin()
    {
        if ($this->imageResized) {
            imagefilter($this->imageResized, IMG_FILTER_GRAYSCALE);
            imagefilter($this->imageResized, IMG_FILTER_BRIGHTNESS, -15);
            imagefilter($this->imageResized, IMG_FILTER_CONTRAST, -15);
            $this->imageResized = $this->gd_apply_overlay($this->imageResized, 'vignette', 100);
        }
    }

    ## --------------------------------------------------------

    public function gd_filter_vintage()
    {
        if ($this->imageResized) {
            $this->imageResized = $this->gd_apply_overlay($this->imageResized, 'vignette', 45);
            imagefilter($this->imageResized, IMG_FILTER_BRIGHTNESS, 20);
            imagefilter($this->imageResized, IMG_FILTER_CONTRAST, -35);
            imagefilter($this->imageResized, IMG_FILTER_COLORIZE, 60, -10, 35);
            imagefilter($this->imageResized, IMG_FILTER_SMOOTH, 7);
            $this->imageResized = $this->gd_apply_overlay($this->imageResized, 'scratch', 10);
        }
    }

    ## --------------------------------------------------------

    /** Apply a PNG overlay
     * @param $im
     * @param $type
     * @param $amount
     * @return mixed
     */
    private function gd_apply_overlay($im, $type, $amount)
    {
        $width = imagesx($im);
        $height = imagesy($im);
        $filter = imagecreatetruecolor($width, $height);

        imagealphablending($filter, false);
        imagesavealpha($filter, true);

        $transparent = imagecolorallocatealpha($filter, 255, 255, 255, 127);
        imagefilledrectangle($filter, 0, 0, $width, $height, $transparent);

        // *** Resize overlay
        $overlay = $this->filterOverlayPath . '/' . $type . '.png';
        $png = imagecreatefrompng($overlay);
        imagecopyresampled($filter, $png, 0, 0, 0, 0, $width, $height, imagesx($png), imagesy($png));

        $comp = imagecreatetruecolor($width, $height);
        imagecopy($comp, $im, 0, 0, 0, 0, $width, $height);
        imagecopy($comp, $filter, 0, 0, 0, 0, $width, $height);
        imagecopymerge($im, $comp, 0, 0, 0, 0, $width, $height, $amount);

        imagedestroy($comp);

        return $im;
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Colorise
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param $rgb
     * @return bool
     */
    public function image_colorize($rgb)
    {
        imagetruecolortopalette($this->imageResized, true, 256);
        $numColors = imagecolorstotal($this->imageResized);

        for ($x = 0; $x < $numColors; ++$x) {
            list($r, $g, $b) = array_values(imagecolorsforindex($this->imageResized, $x));

            // calculate grayscale in percent
            $grayscale = ($r + $g + $b) / 3 / 0xff;

            imagecolorset(
                $this->imageResized,
                $x,
                $grayscale * $rgb[0],
                $grayscale * $rgb[1],
                $grayscale * $rgb[2]
            );
        }

        return true;
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Reflection
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param int    $reflectionHeight
     * @param int    $startingTransparency
     * @param bool   $inside
     * @param string $bgColor
     * @param bool   $stretch
     * @param int    $divider
     */
    public function addReflection($reflectionHeight = 50, $startingTransparency = 30, $inside = false, $bgColor = '#fff', $stretch = false, $divider = 0)
    {
        // *** Convert color
        $rgbArray = $this->formatColor($bgColor);
        $r = $rgbArray['r'];
        $g = $rgbArray['g'];
        $b = $rgbArray['b'];

        $im = $this->imageResized;
        $li = imagecreatetruecolor($this->width, 1);

        $bgc = imagecolorallocate($li, $r, $g, $b);
        imagefilledrectangle($li, 0, 0, $this->width, 1, $bgc);

        $bg = imagecreatetruecolor($this->width, $reflectionHeight);
        $wh = imagecolorallocate($im, 255, 255, 255);

        $im = imagerotate($im, -180, $wh);
        imagecopyresampled($bg, $im, 0, 0, 0, 0, $this->width, $this->height, $this->width, $this->height);

        $im = $bg;

        $bg = imagecreatetruecolor($this->width, $reflectionHeight);

        for ($x = 0; $x < $this->width; ++$x) {
            imagecopy($bg, $im, $x, 0, $this->width - $x - 1, 0, 1, $reflectionHeight);
        }
        $im = $bg;

        $transaprencyAmount = $this->invertTransparency($startingTransparency, 100);

        // *** Fade
        if ($stretch) {
            $step = 100 / ($reflectionHeight + $startingTransparency);
        } else {
            $step = 100 / $reflectionHeight;
        }
        for ($i = 0; $i <= $reflectionHeight; ++$i) {
            if ($startingTransparency > 100) {
                $startingTransparency = 100;
            }
            if ($startingTransparency < 1) {
                $startingTransparency = 1;
            }
            imagecopymerge($bg, $li, 0, $i, 0, 0, $this->width, 1, $startingTransparency);
            $startingTransparency += $step;
        }

        // *** Apply fade
        imagecopymerge($im, $li, 0, 0, 0, 0, $this->width, $divider, 100); // Divider

        // *** width, height of reflection.
        $x = imagesx($im);
        $y = imagesy($im);

        // *** Determines if the reflection should be displayed inside or outside the image
        if ($inside) {
            // Create new blank image with sizes.
            $final = imagecreatetruecolor($this->width, $this->height);

            imagecopymerge($final, $this->imageResized, 0, 0, 0, $reflectionHeight, $this->width, $this->height - $reflectionHeight, 100);
            imagecopymerge($final, $im, 0, $this->height - $reflectionHeight, 0, 0, $x, $y, 100);
        } else {
            // Create new blank image with sizes.
            $final = imagecreatetruecolor($this->width, $this->height + $y);

            imagecopymerge($final, $this->imageResized, 0, 0, 0, 0, $this->width, $this->height, 100);
            imagecopymerge($final, $im, 0, $this->height, 0, 0, $x, $y, 100);
        }

        $this->imageResized = $final;

        imagedestroy($li);
        imagedestroy($im);
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Rotate
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param int    $value
     * @param string $bgColor
     */
    public function rotate($value = 90, $bgColor = 'transparent')
    {
        if ($this->imageResized) {
            if (is_int($value)) {
                $degrees = $value;
            }

            // *** Convert color
            $rgbArray = $this->formatColor($bgColor);
            $r = $rgbArray['r'];
            $g = $rgbArray['g'];
            $b = $rgbArray['b'];
            if (isset($rgbArray['a'])) {
                $a = $rgbArray['a'];
            }

            if (is_string($value)) {
                $value = mb_strtolower($value);

                switch ($value) {
                    case 'left':
                        $degrees = 90;
                        break;
                    case 'right':
                        $degrees = 270;
                        break;
                    case 'upside':
                        $degrees = 180;
                        break;
                    default:
                        break;
                }
            }

            // *** The default direction of imageRotate() is counter clockwise
            //   * This makes it clockwise
            $degrees = 360 - $degrees;

            // *** Create background color
            $bg = imagecolorallocatealpha($this->imageResized, $r, $g, $b, $a);

            // *** Fill with background
            imagefill($this->imageResized, 0, 0, $bg);

            // *** Rotate
            $this->imageResized = imagerotate($this->imageResized, $degrees, $bg); // Rotate 45 degrees and allocated the transparent colour as the one to make transparent (obviously)

            // Ensure alpha transparency
            imagesavealpha($this->imageResized, true);
        }
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Round corners
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param int    $radius
     * @param string $bgColor
     */
    public function roundCorners($radius = 5, $bgColor = 'transparent')
    {
        // *** Check if the user wants transparency
        $isTransparent = false;
        if (!is_array($bgColor)) {
            if ('transparent' === mb_strtolower($bgColor)) {
                $isTransparent = true;
            }
        }

        // *** If we use transparency, we need to color our curved mask with a unique color
        if ($isTransparent) {
            $bgColor = $this->findUnusedGreen();
        }

        // *** Convert color
        $rgbArray = $this->formatColor($bgColor);
        $r = $rgbArray['r'];
        $g = $rgbArray['g'];
        $b = $rgbArray['b'];
        if (isset($rgbArray['a'])) {
            $a = $rgbArray['a'];
        }

        // *** Create top-left corner mask (square)
        $cornerImg = imagecreatetruecolor($radius, $radius);
        //$cornerImg = imagecreate($radius, $radius);

        //imagealphablending($cornerImg, true);
        //imagesavealpha($cornerImg, true);

        //imagealphablending($this->imageResized, false);
        //imagesavealpha($this->imageResized, true);

        // *** Give it a color
        $maskColor = imagecolorallocate($cornerImg, 0, 0, 0);

        // *** Replace the mask color (black) to transparent
        imagecolortransparent($cornerImg, $maskColor);

        // *** Create the image background color
        $imagebgColor = imagecolorallocate($cornerImg, $r, $g, $b);

        // *** Fill the corner area to the user defined color
        imagefill($cornerImg, 0, 0, $imagebgColor);

        imagefilledellipse($cornerImg, $radius, $radius, $radius * 2, $radius * 2, $maskColor);

        // *** Map to top left corner
        imagecopymerge($this->imageResized, $cornerImg, 0, 0, 0, 0, $radius, $radius, 100); #tl

        // *** Map rounded corner to other corners by rotating and applying the mask
        $cornerImg = imagerotate($cornerImg, 90, 0);
        imagecopymerge($this->imageResized, $cornerImg, 0, $this->height - $radius, 0, 0, $radius, $radius, 100); #bl

        $cornerImg = imagerotate($cornerImg, 90, 0);
        imagecopymerge($this->imageResized, $cornerImg, $this->width - $radius, $this->height - $radius, 0, 0, $radius, $radius, 100); #br

        $cornerImg = imagerotate($cornerImg, 90, 0);
        imagecopymerge($this->imageResized, $cornerImg, $this->width - $radius, 0, 0, 0, $radius, $radius, 100); #tr

        // *** If corners are to be transparent, we fill our chromakey color as transparent.
        if ($isTransparent) {
            //imagecolortransparent($this->imageResized, $imagebgColor);
            $this->imageResized = $this->transparentImage($this->imageResized);
            imagesavealpha($this->imageResized, true);
        }
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Shadow
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param int    $shadowAngle
     * @param int    $blur
     * @param string $bgColor
     */
    public function addShadow($shadowAngle = 45, $blur = 15, $bgColor = 'transparent')
    {
        // *** A higher number results in a smoother shadow
        define('STEPS', $blur * 2);

        // *** Set the shadow distance
        $shadowDistance = $blur * 0.25;

        // *** Set blur width and height
        $blurWidth = $blurHeight = $blur;

        if (0 == $shadowAngle) {
            $distWidth = 0;
            $distHeight = 0;
        } else {
            $distWidth = $shadowDistance * cos(deg2rad($shadowAngle));
            $distHeight = $shadowDistance * sin(deg2rad($shadowAngle));
        }

        // *** Convert color
        if ('transparent' !== mb_strtolower($bgColor)) {
            $rgbArray = $this->formatColor($bgColor);
            $r0 = $rgbArray['r'];
            $g0 = $rgbArray['g'];
            $b0 = $rgbArray['b'];
        }

        $image = $this->imageResized;
        $width = $this->width;
        $height = $this->height;

        $newImage = imagecreatetruecolor($width, $height);
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $width, $height, $width, $height);

        // *** RGB
        $rgb = imagecreatetruecolor($width + $blurWidth, $height + $blurHeight);
        $colour = imagecolorallocate($rgb, 0, 0, 0);
        imagefilledrectangle($rgb, 0, 0, $width + $blurWidth, $height + $blurHeight, $colour);
        $colour = imagecolorallocate($rgb, 255, 255, 255);
        //imagefilledrectangle($rgb, $blurWidth*0.5-$distWidth, $blurHeight*0.5-$distHeight, $width+$blurWidth*0.5-$distWidth, $height+$blurWidth*0.5-$distHeight, $colour);
        imagefilledrectangle($rgb, $blurWidth * 0.5 - $distWidth, $blurHeight * 0.5 - $distHeight, $width + $blurWidth * 0.5 - $distWidth, $height + $blurWidth * 0.5 - $distHeight, $colour);
        //imagecopymerge($rgb, $newImage, 1+$blurWidth*0.5-$distWidth, 1+$blurHeight*0.5-$distHeight, 0,0, $width, $height, 100);
        imagecopymerge($rgb, $newImage, $blurWidth * 0.5 - $distWidth, $blurHeight * 0.5 - $distHeight, 0, 0, $width + $blurWidth, $height + $blurHeight, 100);

        // *** Shadow (alpha)
        $shadow = imagecreatetruecolor($width + $blurWidth, $height + $blurHeight);
        imagealphablending($shadow, false);
        $colour = imagecolorallocate($shadow, 0, 0, 0);
        imagefilledrectangle($shadow, 0, 0, $width + $blurWidth, $height + $blurHeight, $colour);

        for ($i = 0; $i <= STEPS; ++$i) {
            $t = ((1.0 * $i) / STEPS);
            $intensity = 255 * $t * $t;

            $colour = imagecolorallocate($shadow, $intensity, $intensity, $intensity);
            $points = [
                $blurWidth * $t,
                $blurHeight,     // Point 1 (x, y)
                $blurWidth,
                $blurHeight * $t,  // Point 2 (x, y)
                $width,
                $blurHeight * $t,  // Point 3 (x, y)
                $width + $blurWidth * (1 - $t),
                $blurHeight,     // Point 4 (x, y)
                $width + $blurWidth * (1 - $t),
                $height,     // Point 5 (x, y)
                $width,
                $height + $blurHeight * (1 - $t),  // Point 6 (x, y)
                $blurWidth,
                $height + $blurHeight * (1 - $t),  // Point 7 (x, y)
                $blurWidth * $t,
                $height,      // Point 8 (x, y)
            ];
            imagepolygon($shadow, $points, 8, $colour);
        }

        for ($i = 0; $i <= STEPS; ++$i) {
            $t = ((1.0 * $i) / STEPS);
            $intensity = 255 * $t * $t;

            $colour = imagecolorallocate($shadow, $intensity, $intensity, $intensity);
            imagefilledarc($shadow, $blurWidth - 1, $blurHeight - 1, 2 * (1 - $t) * $blurWidth, 2 * (1 - $t) * $blurHeight, 180, 268, $colour, IMG_ARC_PIE);
            imagefilledarc($shadow, $width, $blurHeight - 1, 2 * (1 - $t) * $blurWidth, 2 * (1 - $t) * $blurHeight, 270, 358, $colour, IMG_ARC_PIE);
            imagefilledarc($shadow, $width, $height, 2 * (1 - $t) * $blurWidth, 2 * (1 - $t) * $blurHeight, 0, 90, $colour, IMG_ARC_PIE);
            imagefilledarc($shadow, $blurWidth - 1, $height, 2 * (1 - $t) * $blurWidth, 2 * (1 - $t) * $blurHeight, 90, 180, $colour, IMG_ARC_PIE);
        }

        $colour = imagecolorallocate($shadow, 255, 255, 255);
        imagefilledrectangle($shadow, $blurWidth, $blurHeight, $width, $height, $colour);
        imagefilledrectangle($shadow, $blurWidth * 0.5 - $distWidth, $blurHeight * 0.5 - $distHeight, $width + $blurWidth * 0.5 - 1 - $distWidth, $height + $blurHeight * 0.5 - 1 - $distHeight, $colour);

        // *** The magic
        imagealphablending($rgb, false);

        for ($theX = 0, $theXMax = imagesx($rgb); $theX < $theXMax; $theX++) {
            for ($theY = 0, $theYMax = imagesy($rgb); $theY < $theYMax; $theY++) {
                // *** Get the RGB values for every pixel of the RGB image
                $colArray = imagecolorat($rgb, $theX, $theY);
                $r = ($colArray >> 16) & 0xFF;
                $g = ($colArray >> 8) & 0xFF;
                $b = $colArray & 0xFF;

                // *** Get the alpha value for every pixel of the shadow image
                $colArray = imagecolorat($shadow, $theX, $theY);
                $a = $colArray & 0xFF;
                $a = 127 - floor($a / 2);
                $t = $a / 128.0;

                // *** Create color
                if ('transparent' === mb_strtolower($bgColor)) {
                    $myColour = imagecolorallocatealpha($rgb, $r, $g, $b, $a);
                } else {
                    $myColour = imagecolorallocate($rgb, $r * (1.0 - $t) + $r0 * $t, $g * (1.0 - $t) + $g0 * $t, $b * (1.0 - $t) + $b0 * $t);
                }

                // *** Add color to new rgb image
                imagesetpixel($rgb, $theX, $theY, $myColour);
            }
        }

        imagealphablending($rgb, true);
        imagesavealpha($rgb, true);

        $this->imageResized = $rgb;

        imagedestroy($image);
        imagedestroy($newImage);
        imagedestroy($shadow);
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Add Caption Box
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param string $side
     * @param int    $thickness
     * @param int    $padding
     * @param string $bgColor
     * @param int    $transaprencyAmount
     */
    public function addCaptionBox($side = 'b', $thickness = 50, $padding = 0, $bgColor = '#000', $transaprencyAmount = 30)
    {
        $side = mb_strtolower($side);

        // *** Convert color
        $rgbArray = $this->formatColor($bgColor);
        $r = $rgbArray['r'];
        $g = $rgbArray['g'];
        $b = $rgbArray['b'];

        $positionArray = $this->calculateCaptionBoxPosition($side, $thickness, $padding);

        // *** Store incase we want to use method addTextToCaptionBox()
        $this->captionBoxPositionArray = $positionArray;

        $transaprencyAmount = $this->invertTransparency($transaprencyAmount, 127, false);
        $transparent = imagecolorallocatealpha($this->imageResized, $r, $g, $b, $transaprencyAmount);
        imagefilledrectangle($this->imageResized, $positionArray['x1'], $positionArray['y1'], $positionArray['x2'], $positionArray['y2'], $transparent);
    }

    ## --------------------------------------------------------

    /**
     * @param        $text
     * @param string $fontColor
     * @param int    $fontSize
     * @param int    $angle
     * @param null   $font
     * @return bool
     */
    public function addTextToCaptionBox($text, $fontColor = '#fff', $fontSize = 12, $angle = 0, $font = null)
    {
        // *** Get the caption box measurements
        if (4 == count($this->captionBoxPositionArray)) {
            $x1 = $this->captionBoxPositionArray['x1'];
            $x2 = $this->captionBoxPositionArray['x2'];
            $y1 = $this->captionBoxPositionArray['y1'];
            $y2 = $this->captionBoxPositionArray['y2'];
        } else {
            if ($this->debug) {
                die('No caption box found.');
            }

            return false;
        }

        // *** Get text font
        try {
            $font = $this->getTextFont($font);
        } catch (Exception $e) {
        }

        // *** Get text size
        $textSizeArray = $this->getTextSize($fontSize, $angle, $font, $text);
        $textWidth = $textSizeArray['width'];
        $textHeight = $textSizeArray['height'];

        // *** Find the width/height middle points
        $boxXMiddle = (($x2 - $x1) / 2);
        $boxYMiddle = (($y2 - $y1) / 2);

        // *** Box middle - half the text width/height
        $xPos = ($x1 + $boxXMiddle) - ($textWidth / 2);
        $yPos = ($y1 + $boxYMiddle) - ($textHeight / 2);

        $pos = $xPos . 'x' . $yPos;

        $this->addText($text, $pos, $padding = 0, $fontColor, $fontSize, $angle, $font);
    }

    ## --------------------------------------------------------

    /**
     * @param $side
     * @param $thickness
     * @param $padding
     * @return array
     */
    private function calculateCaptionBoxPosition($side, $thickness, $padding)
    {
        $positionArray = [];

        switch ($side) {
            case 't':
                $positionArray['x1'] = 0;
                $positionArray['y1'] = $padding;
                $positionArray['x2'] = $this->width;
                $positionArray['y2'] = $thickness + $padding;
                break;
            case 'r':
                $positionArray['x1'] = $this->width - $thickness - $padding;
                $positionArray['y1'] = 0;
                $positionArray['x2'] = $this->width - $padding;
                $positionArray['y2'] = $this->height;
                break;
            case 'b':
                $positionArray['x1'] = 0;
                $positionArray['y1'] = $this->height - $thickness - $padding;
                $positionArray['x2'] = $this->width;
                $positionArray['y2'] = $this->height - $padding;
                break;
            case 'l':
                $positionArray['x1'] = $padding;
                $positionArray['y1'] = 0;
                $positionArray['x2'] = $thickness + $padding;
                $positionArray['y2'] = $this->height;
                break;
            default:
                break;
        }

        return $positionArray;
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Get EXIF Data
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param bool $debug
     * @return array
     */
    public function getExif($debug = true)
    {
        if (!$this->debug || !$debug) {
            $debug = false;
        }

        // *** Check all is good - check the EXIF library exists and the file exists, too.
        if (!$this->testEXIFInstalled()) {
            if ($debug) {
                die('The EXIF Library is not installed.');
            }

            return [];
        }
        if (!file_exists($this->fileName)) {
            if ($debug) {
                die('Image not found.');
            }

            return [];
        }
        if ('.jpg' !== $this->fileExtension) {
            if ($debug) {
                die('Metadata not supported for this image type.');
            }

            return [];
        }
        $exifData = exif_read_data($this->fileName, 'IFD0');

        // *** Format the apperture value
        $ev = isset($exifData['ApertureValue']) ? $exifData['ApertureValue'] : '';
        $apPeicesArray = explode('/', $ev);
        if (2 == count($apPeicesArray)) {
            $apertureValue = round($apPeicesArray[0] / $apPeicesArray[1], 2, PHP_ROUND_HALF_DOWN) . ' EV';
        } else {
            $apertureValue = '';
        }

        // *** Format the focal length
        $focalLength = isset($exifData['FocalLength']) ? $exifData['FocalLength'] : '';
        $flPeicesArray = explode('/', $focalLength);
        if (2 == count($flPeicesArray)) {
            $focalLength = $flPeicesArray[0] / $flPeicesArray[1] . '.0 mm';
        } else {
            $focalLength = '';
        }

        // *** Format fNumber
        $fNumber = isset($exifData['FNumber']) ? $exifData['FNumber'] : '';
        $fnPeicesArray = explode('/', $fNumber);
        if (2 == count($fnPeicesArray)) {
            $fNumber = $fnPeicesArray[0] / $fnPeicesArray[1];
        } else {
            $fNumber = '';
        }

        // *** Resolve ExposureProgram
        if (isset($exifData['ExposureProgram'])) {
            $ep = $exifData['ExposureProgram'];
        }
        if (isset($ep)) {
            $ep = $this->resolveExposureProgram($ep);
        }

        // *** Resolve MeteringMode
        $mm = isset($exifData['MeteringMode']) ? $exifData['MeteringMode'] : '';
        $mm = $this->resolveMeteringMode($mm);

        // *** Resolve Flash
        $flash = isset($exifData['Flash']) ? $exifData['Flash'] : '';
        $flash = $this->resolveFlash($flash);

        if (isset($exifData['Make'])) {
            $exifDataArray['make'] = $exifData['Make'];
        } else {
            $exifDataArray['make'] = '';
        }

        if (isset($exifData['Model'])) {
            $exifDataArray['model'] = $exifData['Model'];
        } else {
            $exifDataArray['model'] = '';
        }

        if (isset($exifData['DateTime'])) {
            $exifDataArray['date'] = $exifData['DateTime'];
        } else {
            $exifDataArray['date'] = '';
        }

        if (isset($exifData['ExposureTime'])) {
            $exifDataArray['exposure time'] = $exifData['ExposureTime'] . ' sec.';
        } else {
            $exifDataArray['exposure time'] = '';
        }

        if ('' != $apertureValue) {
            $exifDataArray['aperture value'] = $apertureValue;
        } else {
            $exifDataArray['aperture value'] = '';
        }

        if (isset($exifData['COMPUTED']['ApertureFNumber'])) {
            $exifDataArray['f-stop'] = $exifData['COMPUTED']['ApertureFNumber'];
        } else {
            $exifDataArray['f-stop'] = '';
        }

        if (isset($exifData['FNumber'])) {
            $exifDataArray['fnumber'] = $exifData['FNumber'];
        } else {
            $exifDataArray['fnumber'] = '';
        }

        if ('' != $fNumber) {
            $exifDataArray['fnumber value'] = $fNumber;
        } else {
            $exifDataArray['fnumber value'] = '';
        }

        if (isset($exifData['ISOSpeedRatings'])) {
            $exifDataArray['iso'] = $exifData['ISOSpeedRatings'];
        } else {
            $exifDataArray['iso'] = '';
        }

        if ('' != $focalLength) {
            $exifDataArray['focal length'] = $focalLength;
        } else {
            $exifDataArray['focal length'] = '';
        }

        if (isset($ep)) {
            $exifDataArray['exposure program'] = $ep;
        } else {
            $exifDataArray['exposure program'] = '';
        }

        if ('' != $mm) {
            $exifDataArray['metering mode'] = $mm;
        } else {
            $exifDataArray['metering mode'] = '';
        }

        if ('' != $flash) {
            $exifDataArray['flash status'] = $flash;
        } else {
            $exifDataArray['flash status'] = '';
        }

        if (isset($exifData['Artist'])) {
            $exifDataArray['creator'] = $exifData['Artist'];
        } else {
            $exifDataArray['creator'] = '';
        }

        if (isset($exifData['Copyright'])) {
            $exifDataArray['copyright'] = $exifData['Copyright'];
        } else {
            $exifDataArray['copyright'] = '';
        }

        // *** Orientation
        if (isset($exifData['Orientation'])) {
            $exifDataArray['orientation'] = $exifData['Orientation'];
        } else {
            $exifDataArray['orientation'] = '';
        }

        return $exifDataArray;
    }

    ## --------------------------------------------------------

    /**
     * @param $ep
     * @return string
     */
    private function resolveExposureProgram($ep)
    {
        switch ($ep) {
            case 0:
                $ep = '';
                break;
            case 1:
                $ep = 'manual';
                break;
            case 2:
                $ep = 'normal program';
                break;
            case 3:
                $ep = 'aperture priority';
                break;
            case 4:
                $ep = 'shutter priority';
                break;
            case 5:
                $ep = 'creative program';
                break;
            case 6:
                $ep = 'action program';
                break;
            case 7:
                $ep = 'portrait mode';
                break;
            case 8:
                $ep = 'landscape mode';
                break;
            default:
                break;
        }

        return $ep;
    }

    ## --------------------------------------------------------

    /**
     * @param $mm
     * @return string
     */
    private function resolveMeteringMode($mm)
    {
        switch ($mm) {
            case 0:
                $mm = 'unknown';
                break;
            case 1:
                $mm = 'average';
                break;
            case 2:
                $mm = 'center weighted average';
                break;
            case 3:
                $mm = 'spot';
                break;
            case 4:
                $mm = 'multi spot';
                break;
            case 5:
                $mm = 'pattern';
                break;
            case 6:
                $mm = 'partial';
                break;
            case 255:
                $mm = 'other';
                break;
            default:
                break;
        }

        return $mm;
    }

    ## --------------------------------------------------------

    /**
     * @param $flash
     * @return string
     */
    private function resolveFlash($flash)
    {
        switch ($flash) {
            case 0:
                $flash = 'flash did not fire';
                break;
            case 1:
                $flash = 'flash fired';
                break;
            case 5:
                $flash = 'strobe return light not detected';
                break;
            case 7:
                $flash = 'strobe return light detected';
                break;
            case 9:
                $flash = 'flash fired, compulsory flash mode';
                break;
            case 13:
                $flash = 'flash fired, compulsory flash mode, return light not detected';
                break;
            case 15:
                $flash = 'flash fired, compulsory flash mode, return light detected';
                break;
            case 16:
                $flash = 'flash did not fire, compulsory flash mode';
                break;
            case 24:
                $flash = 'flash did not fire, auto mode';
                break;
            case 25:
                $flash = 'flash fired, auto mode';
                break;
            case 29:
                $flash = 'flash fired, auto mode, return light not detected';
                break;
            case 31:
                $flash = 'flash fired, auto mode, return light detected';
                break;
            case 32:
                $flash = 'no flash function';
                break;
            case 65:
                $flash = 'flash fired, red-eye reduction mode';
                break;
            case 69:
                $flash = 'flash fired, red-eye reduction mode, return light not detected';
                break;
            case 71:
                $flash = 'flash fired, red-eye reduction mode, return light detected';
                break;
            case 73:
                $flash = 'flash fired, compulsory flash mode, red-eye reduction mode';
                break;
            case 77:
                $flash = 'flash fired, compulsory flash mode, red-eye reduction mode, return light not detected';
                break;
            case 79:
                $flash = 'flash fired, compulsory flash mode, red-eye reduction mode, return light detected';
                break;
            case 89:
                $flash = 'flash fired, auto mode, red-eye reduction mode';
                break;
            case 93:
                $flash = 'flash fired, auto mode, return light not detected, red-eye reduction mode';
                break;
            case 95:
                $flash = 'flash fired, auto mode, return light detected, red-eye reduction mode';
                break;
            default:
                break;
        }

        return $flash;
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Get IPTC Data
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Write IPTC Data
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param $value
     */
    public function writeIPTCcaption($value)
    {
        $this->writeIPTC(120, $value);
    }

    ## --------------------------------------------------------

    /**
     * @param $value
     */
    public function writeIPTCwriter($value)
    {
        //$this->writeIPTC(65, $value);
    }

    ## --------------------------------------------------------

    /**
     * @param $dat
     * @param $value
     */
    private function writeIPTC($dat, $value)
    {
        # LIMIT TO JPG

        $caption_block = $this->iptc_maketag(2, $dat, $value);
        $image_string = iptcembed($caption_block, $this->fileName);
        file_put_contents('iptc.jpg', $image_string);
    }

    ## --------------------------------------------------------

    /**
     * @param $rec
     * @param $dat
     * @param $val
     * @return string
     */
    private function iptc_maketag($rec, $dat, $val)
    {
        $len = mb_strlen($val);
        if ($len < 0x8000) {
            return chr(0x1c) . chr($rec) . chr($dat) . chr($len >> 8) . chr($len & 0xff) . $val;
        }

        return chr(0x1c) . chr($rec) . chr($dat) . chr(0x80) . chr(0x04) . chr(($len >> 24) & 0xff) . chr(($len >> 16) & 0xff) . chr(($len >> 8) & 0xff) . chr(($len) & 0xff) . $val;
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Write XMP Data
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    //http://xmpphptoolkit.sourceforge.net/

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Add Text
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param        $text
     * @param string $pos
     * @param int    $padding
     * @param string $fontColor
     * @param int    $fontSize
     * @param int    $angle
     * @param null   $font
     */
    public function addText($text, $pos = '20x20', $padding = 0, $fontColor = '#fff', $fontSize = 12, $angle = 0, $font = null)
    {
        // *** Convert color
        $rgbArray = $this->formatColor($fontColor);
        $r = $rgbArray['r'];
        $g = $rgbArray['g'];
        $b = $rgbArray['b'];

        // *** Get text font
        try {
            $font = $this->getTextFont($font);
        } catch (Exception $e) {
        }

        // *** Get text size
        $textSizeArray = $this->getTextSize($fontSize, $angle, $font, $text);
        $textWidth = $textSizeArray['width'];
        $textHeight = $textSizeArray['height'];

        // *** Find co-ords to place text
        $posArray = $this->calculatePosition($pos, $padding, $textWidth, $textHeight, false);
        $x = $posArray['width'];
        $y = $posArray['height'];

        $fontColor = imagecolorallocate($this->imageResized, $r, $g, $b);

        // *** Add text
        imagettftext($this->imageResized, $fontSize, $angle, $x, $y, $fontColor, $font, $text);
    }

    ## --------------------------------------------------------

    /**
     * @param $font
     * @return bool|string
     */
    private function getTextFont($font)
    {
        // *** Font path (shou
        $fontPath = __DIR__ . '/' . $this->fontDir;

        // *** The below is/may be needed depending on your version (see ref)
        putenv('GDFONTPATH=' . realpath('.'));

        // *** Check if the passed in font exsits...
        if (null == $font || !file_exists($font)) {
            // *** ...If not, default to this font.
            $font = $fontPath . '/arimo.ttf';

            // *** Check our default font exists...
            if (!file_exists($font)) {
                // *** If not, return false
                if ($this->debug) {
                    die('Font not found');
                }

                return false;
            }
        }

        return $font;
    }

    ## --------------------------------------------------------

    /**
     * @param $fontSize
     * @param $angle
     * @param $font
     * @param $text
     * @return array
     */
    private function getTextSize($fontSize, $angle, $font, $text)
    {
        // *** Define box (so we can get the width)
        $box = @imagettfbbox($fontSize, $angle, $font, $text);

        // ***  Get width of text from dimensions
        $textWidth = abs($box[4] - $box[0]);

        // ***  Get height of text from dimensions (should also be same as $fontSize)
        $textHeight = abs($box[5] - $box[1]);

        return ['height' => $textHeight, 'width' => $textWidth];
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        Add Watermark
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param     $watermarkImage
     * @param     $pos
     * @param int $padding
     * @param int $opacity
     */
    public function addWatermark($watermarkImage, $pos, $padding = 0, $opacity = 0)
    {
        // Load the stamp and the photo to apply the watermark to
        try {
            $stamp = $this->openImage($watermarkImage);
        } catch (Exception $e) {
        }    # stamp
        $im = $this->imageResized;            # photo

        // *** Get stamps width and height
        $sx = imagesx($stamp);
        $sy = imagesy($stamp);

        // *** Find co-ords to place image
        $posArray = $this->calculatePosition($pos, $padding, $sx, $sy);
        $x = $posArray['width'];
        $y = $posArray['height'];

        // *** Set watermark opacity
        if ('.png' === mb_strtolower(mb_strrchr($watermarkImage, '.'))) {
            $opacity = $this->invertTransparency($opacity, 100);
            $this->filterOpacity($stamp, $opacity);
        }

        // Copy the watermark image onto our photo
        imagecopy($im, $stamp, $x, $y, 0, 0, imagesx($stamp), imagesy($stamp));
    }

    ## --------------------------------------------------------

    /**
     * @param      $pos
     * @param      $padding
     * @param      $assetWidth
     * @param      $assetHeight
     * @param bool $upperLeft
     * @return array
     */
    private function calculatePosition($pos, $padding, $assetWidth, $assetHeight, $upperLeft = true)
    {
        $pos = mb_strtolower($pos);

        // *** If co-ords have been entered
        if (mb_strstr($pos, 'x')) {
            $pos = str_replace(' ', '', $pos);

            $xyArray = explode('x', $pos);
            list($width, $height) = $xyArray;
        } else {
            switch ($pos) {
                case 'tl':
                    $width = 0 + $padding;
                    $height = 0 + $padding;
                    break;
                case 't':
                    $width = ($this->width / 2) - ($assetWidth / 2);
                    $height = 0 + $padding;
                    break;
                case 'tr':
                    $width = $this->width - $assetWidth - $padding;
                    $height = 0 + $padding;
                    break;
                case 'l':
                    $width = 0 + $padding;
                    $height = ($this->height / 2) - ($assetHeight / 2);
                    break;
                case 'm':
                    $width = ($this->width / 2) - ($assetWidth / 2);
                    $height = ($this->height / 2) - ($assetHeight / 2);
                    break;
                case 'r':
                    $width = $this->width - $assetWidth - $padding;
                    $height = ($this->height / 2) - ($assetHeight / 2);
                    break;
                case 'bl':
                    $width = 0 + $padding;
                    $height = $this->height - $assetHeight - $padding;
                    break;
                case 'b':
                    $width = ($this->width / 2) - ($assetWidth / 2);
                    $height = $this->height - $assetHeight - $padding;
                    break;
                case 'br':
                    $width = $this->width - $assetWidth - $padding;
                    $height = $this->height - $assetHeight - $padding;
                    break;
                default:
                    $width = 0;
                    $height = 0;
                    break;
            }
        }

        if (!$upperLeft) {
            $height = $height + $assetHeight;
        }

        return ['width' => $width, 'height' => $height];
    }

    ## --------------------------------------------------------

    /**
     * @param     $img
     * @param int $opacity
     * @return bool
     */
    private function filterOpacity(&$img, $opacity = 75)
    {
        if (!isset($opacity)) {
            return false;
        }

        if (100 == $opacity) {
            return true;
        }

        $opacity /= 100;

        //get image width and height
        $w = imagesx($img);
        $h = imagesy($img);

        //turn alpha blending off
        imagealphablending($img, false);

        //find the most opaque pixel in the image (the one with the smallest alpha value)
        $minalpha = 127;
        for ($x = 0; $x < $w; ++$x) {
            for ($y = 0; $y < $h; ++$y) {
                $alpha = (imagecolorat($img, $x, $y) >> 24) & 0xFF;
                if ($alpha < $minalpha) {
                    $minalpha = $alpha;
                }
            }
        }

        //loop through image pixels and modify alpha for each
        for ($x = 0; $x < $w; ++$x) {
            for ($y = 0; $y < $h; ++$y) {
                //get current alpha value (represents the TANSPARENCY!)
                $colorxy = imagecolorat($img, $x, $y);
                $alpha = ($colorxy >> 24) & 0xFF;
                //calculate new alpha
                if (127 !== $minalpha) {
                    $alpha = 127 + 127 * $opacity * ($alpha - 127) / (127 - $minalpha);
                } else {
                    $alpha += 127 * $opacity;
                }
                //get the color index with new alpha
                $alphacolorxy = imagecolorallocatealpha($img, ($colorxy >> 16) & 0xFF, ($colorxy >> 8) & 0xFF, $colorxy & 0xFF, $alpha);
                //set pixel with the new color + opacity
                if (!imagesetpixel($img, $x, $y, $alphacolorxy)) {
                    return false;
                }
            }
        }

        return true;
    }

    ## --------------------------------------------------------

    /**
     * @param $file
     * @return bool|false|resource|string
     */
    private function openImage($file)
    {
        if (!file_exists($file) && !$this->checkStringStartsWith('http://', $file)) {
            if ($this->debug) {
                die('Image not found.');
            }
            die();
        }

        // *** Get extension
        $extension = mb_strrchr($file, '.');
        $extension = mb_strtolower($extension);

        switch ($extension) {
            case '.jpg':
            case '.jpeg':
                $img = @imagecreatefromjpeg($file);
                break;
            case '.gif':
                $img = @imagecreatefromgif($file);
                break;
            case '.png':
                $img = @imagecreatefrompng($file);
                break;
            case '.bmp':
                $img = @$this->ImageCreateFromBMP($file);
                break;
            case '.psd':
                $img = @$this->imagecreatefrompsd($file);
                break;
            // ... etc

            default:
                $img = false;
                break;
        }

        return $img;
    }

    ## --------------------------------------------------------

    public function reset()
    {
        $this->__destruct();
        $this->image = null;
        $this->imageResized = null;
        gc_collect_cycles();

        try {
            $this->__construct($this->fileName);
        } catch (Exception $e) {
        }
    }

    ## --------------------------------------------------------

    /**
     * @param        $savePath
     * @param string $imageQuality
     */
    public function saveImage($savePath, $imageQuality = '100')
    {
        // *** Perform a check or two.
        if (!is_resource($this->imageResized)) {
            if ($this->debug) {
                die('saveImage: This is not a resource.');
            }
            die();
        }
        $fileInfoArray = pathinfo($savePath);
        clearstatcache();
        if (!is_writable($fileInfoArray['dirname'])) {
            if ($this->debug) {
                die('The path is not writable. Please check your permissions.');
            }
            die();
        }

        // *** Get extension
        $extension = mb_strrchr($savePath, '.');
        $extension = mb_strtolower($extension);

        $error = '';

        switch ($extension) {
            case '.jpg':
            case '.jpeg':
                $this->checkInterlaceImage($this->isInterlace);
                if (imagetypes() & IMG_JPG) {
                    imagejpeg($this->imageResized, $savePath, $imageQuality);
                } else {
                    $error = 'jpg';
                }
                break;
            case '.gif':
                $this->checkInterlaceImage($this->isInterlace);
                if (imagetypes() & IMG_GIF) {
                    imagegif($this->imageResized, $savePath);
                } else {
                    $error = 'gif';
                }
                break;
            case '.png':
                // *** Scale quality from 0-100 to 0-9
                $scaleQuality = round(($imageQuality / 100) * 9);

                // *** Invert qualit setting as 0 is best, not 9
                $invertScaleQuality = 9 - $scaleQuality;

                $this->checkInterlaceImage($this->isInterlace);
                if (imagetypes() & IMG_PNG) {
                    imagepng($this->imageResized, $savePath, $invertScaleQuality);
                } else {
                    $error = 'png';
                }
                break;
            case '.bmp':
                file_put_contents($savePath, $this->GD2BMPstring($this->imageResized));
                break;
            // ... etc

            default:
                // *** No extension - No save.
                $this->errorArray[] = 'This file type (' . $extension . ') is not supported. File not saved.';
                break;
        }

        //imagedestroy($this->imageResized);

        // *** Display error if a file type is not supported.
        if ('' != $error) {
            $this->errorArray[] = $error . ' support is NOT enabled. File not saved.';
        }
    }

    ## --------------------------------------------------------

    /**
     * @param string $fileType
     * @param string $imageQuality
     */
    public function displayImage($fileType = 'jpg', $imageQuality = '100')
    {
        if (!is_resource($this->imageResized)) {
            if ($this->debug) {
                die('saveImage: This is not a resource.');
            }
            die();
        }

        switch ($fileType) {
            case 'jpg':
            case 'jpeg':
                header('Content-type: image/jpeg');
                imagejpeg($this->imageResized, '', $imageQuality);
                break;
            case 'gif':
                header('Content-type: image/gif');
                imagegif($this->imageResized);
                break;
            case 'png':
                header('Content-type: image/png');

                // *** Scale quality from 0-100 to 0-9
                $scaleQuality = round(($imageQuality / 100) * 9);

                // *** Invert qualit setting as 0 is best, not 9
                $invertScaleQuality = 9 - $scaleQuality;

                imagepng($this->imageResized, '', $invertScaleQuality);
                break;
            case 'bmp':
                echo 'bmp file format is not supported.';
                break;
            // ... etc

            default:
                // *** No extension - No save.
                break;
        }
        //imagedestroy($this->imageResized);
    }

    ## --------------------------------------------------------

    /**
     * @param $bool
     */
    public function setTransparency($bool)
    {
        $this->keepTransparency = $bool;
    }

    ## --------------------------------------------------------

    /**
     * @param $value
     */
    public function setFillColor($value)
    {
        $colorArray = $this->formatColor($value);
        $this->fillColorArray = $colorArray;
    }

    ## --------------------------------------------------------

    /**
     * @param $value
     */
    public function setCropFromTop($value)
    {
        $this->cropFromTopPercent = $value;
    }

    ## --------------------------------------------------------

    /**
     * @return bool
     */
    public function testGDInstalled()
    {
        if (extension_loaded('gd') && function_exists('gd_info')) {
            $gdInstalled = true;
        } else {
            $gdInstalled = false;
        }

        return $gdInstalled;
    }

    ## --------------------------------------------------------

    /**
     * @return bool
     */
    public function testEXIFInstalled()
    {
        if (extension_loaded('exif')) {
            $exifInstalled = true;
        } else {
            $exifInstalled = false;
        }

        return $exifInstalled;
    }

    ## --------------------------------------------------------

    /**
     * @return bool
     */
    public function testIsImage()
    {
        $file = $this->fileName;
        $isImage = false;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file);
        finfo_close($finfo);

        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/gif':
            case 'image/png':
            case 'image/bmp':
            case 'image/x-windows-bmp':
                $isImage = true;
                break;
            default:
                $isImage = false;
        }

        return $isImage;
    }

    ## --------------------------------------------------------

    /**
     * @return bool
     */
    public function getIsImage()
    {
        return $this->isImage;
    }

    ## --------------------------------------------------------

    public function testFunct()
    {
        echo $this->height;
    }

    ## --------------------------------------------------------

    /**
     * @param $value
     */
    public function setForceStretch($value)
    {
        $this->forceStretch = $value;
    }

    ## --------------------------------------------------------

    /**
     * @param $fileName
     */
    public function setFile($fileName)
    {
        try {
            $this->__construct($fileName);
        } catch (Exception $e) {
        }
    }

    ## --------------------------------------------------------

    public function getFileName()
    {
        return $this->fileName;
    }

    ## --------------------------------------------------------

    /**
     * @return false|int
     */
    public function getHeight()
    {
        return $this->height;
    }

    ## --------------------------------------------------------

    /**
     * @return false|int
     */
    public function getWidth()
    {
        return $this->width;
    }

    ## --------------------------------------------------------

    /**
     * @return false|int
     */
    public function getOriginalHeight()
    {
        return $this->heightOriginal;
    }

    ## --------------------------------------------------------

    /**
     * @return false|int
     */
    public function getOriginalWidth()
    {
        return $this->widthOriginal;
    }

    ## --------------------------------------------------------

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errorArray;
    }

    ## --------------------------------------------------------

    /**
     * @param $isEnabled
     */
    private function checkInterlaceImage($isEnabled)
    {
        if ($isEnabled) {
            imageinterlace($this->imageResized, $isEnabled);
        }
    }

    ## --------------------------------------------------------

    /**
     * @param $value
     * @return array|int[]
     */
    protected function formatColor($value)
    {
        $rgbArray = [];

        // *** If it's an array it should be R, G, B
        if (is_array($value)) {
            if (0 == key($value) && 3 == count($value)) {
                $rgbArray['r'] = $value[0];
                $rgbArray['g'] = $value[1];
                $rgbArray['b'] = $value[2];
            } else {
                $rgbArray = $value;
            }
        } elseif ('transparent' === mb_strtolower($value)) {
            $rgbArray = [
                'r' => 255,
                'g' => 255,
                'b' => 255,
                'a' => 127,
            ];
        } else {
            // *** ...Else it should be hex. Let's make it RGB
            $rgbArray = $this->hex2dec($value);
        }

        return $rgbArray;
    }

    ## --------------------------------------------------------

    /**
     * @param $hex
     * @return array
     */
    public function hex2dec($hex)
    {
        $color = str_replace('#', '', $hex);

        if (3 == mb_strlen($color)) {
            $color = $color . $color;
        }

        $rgb = [
            'r' => hexdec(mb_substr($color, 0, 2)),
            'g' => hexdec(mb_substr($color, 2, 2)),
            'b' => hexdec(mb_substr($color, 4, 2)),
            'a' => 0,
        ];

        return $rgb;
    }

    ## --------------------------------------------------------

    /**
     * @param $colorArray
     * @return false|int
     */
    private function createImageColor($colorArray)
    {
        $r = $colorArray['r'];
        $g = $colorArray['g'];
        $b = $colorArray['b'];

        return imagecolorallocate($this->imageResized, $r, $g, $b);
    }

    ## --------------------------------------------------------

    /**
     * @param $colorArray
     * @return bool
     */
    private function testColorExists($colorArray)
    {
        $r = $colorArray['r'];
        $g = $colorArray['g'];
        $b = $colorArray['b'];

        if (-1 == imagecolorexact($this->imageResized, $r, $g, $b)) {
            return false;
        }

        return true;
    }

    ## --------------------------------------------------------

    /**
     * @return int[]
     */
    private function findUnusedGreen()
    {
        $green = 255;

        do {
            $greenChroma = [0, $green, 0];
            $colorArray = $this->formatColor($greenChroma);
            $match = $this->testColorExists($colorArray);
            $green--;
        } while (false == $match && $green > 0);

        // *** If no match, just bite the bullet and use green value of 255
        if (!$match) {
            $greenChroma = [0, $green, 0];
        }

        return $greenChroma;
    }

    ## --------------------------------------------------------

    /**
     * @return int[]
     */
    private function findUnusedBlue()
    {
        $blue = 255;

        do {
            $blueChroma = [0, 0, $blue];
            $colorArray = $this->formatColor($blueChroma);
            $match = $this->testColorExists($colorArray);
            $blue--;
        } while (false == $match && $blue > 0);

        // *** If no match, just bite the bullet and use blue value of 255
        if (!$match) {
            $blueChroma = [0, 0, $blue];
        }

        return $blueChroma;
    }

    ## --------------------------------------------------------

    /**
     * @param      $value
     * @param      $originalMax
     * @param bool $invert
     * @return float|int
     */
    private function invertTransparency($value, $originalMax, $invert = true)
    {
        // *** Test max range
        if ($value > $originalMax) {
            $value = $originalMax;
        }

        // *** Test min range
        if ($value < 0) {
            $value = 0;
        }

        if ($invert) {
            return $originalMax - (($value / 100) * $originalMax);
        }

        return ($value / 100) * $originalMax;
    }

    ## --------------------------------------------------------

    /**
     * @param $src
     * @return mixed
     */
    private function transparentImage($src)
    {
        // *** making images with white bg transparent
        $r1 = 0;
        $g1 = 255;
        $b1 = 0;
        for ($x = 0, $xMax = imagesx($src); $x < $xMax; ++$x) {
            for ($y = 0, $yMax = imagesy($src); $y < $yMax; ++$y) {
                $color = imagecolorat($src, $x, $y);
                $r = ($color >> 16) & 0xFF;
                $g = ($color >> 8) & 0xFF;
                $b = $color & 0xFF;
                for ($i = 0; $i < 270; ++$i) {
                    //if ($r . $g . $b == ($r1 + $i) . ($g1 + $i) . ($b1 + $i)) {
                    if (0 == $r && 255 == $g && 0 == $b) {
                        //if ($g == 255) {
                        $trans_colour = imagecolorallocatealpha($src, 0, 0, 0, 127);
                        imagefill($src, $x, $y, $trans_colour);
                    }
                }
            }
        }

        return $src;
    }

    ## --------------------------------------------------------

    /**
     * @param $needle
     * @param $haystack
     * @return bool
     */
    public function checkStringStartsWith($needle, $haystack)
    {
        return (mb_substr($haystack, 0, mb_strlen($needle)) == $needle);
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        BMP SUPPORT (SAVING) - James Heinrich
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param $gd_image
     * @return string
     */
    private function GD2BMPstring($gd_image)
    {
        $imageX = imagesx($gd_image);
        $imageY = imagesy($gd_image);

        $BMP = '';
        for ($y = ($imageY - 1); $y >= 0; $y--) {
            $thisline = '';
            for ($x = 0; $x < $imageX; ++$x) {
                $argb = $this->GetPixelColor($gd_image, $x, $y);
                $thisline .= chr($argb['blue']) . chr($argb['green']) . chr($argb['red']);
            }
            while (mb_strlen($thisline) % 4) {
                $thisline .= "\x00";
            }
            $BMP .= $thisline;
        }

        $bmpSize = mb_strlen($BMP) + 14 + 40;
        // BITMAPFILEHEADER [14 bytes] - http://msdn.microsoft.com/library/en-us/gdi/bitmaps_62uq.asp
        $BITMAPFILEHEADER = 'BM';                                    // WORD    bfType;
        $BITMAPFILEHEADER .= $this->LittleEndian2String($bmpSize, 4); // DWORD   bfSize;
        $BITMAPFILEHEADER .= $this->LittleEndian2String(0, 2); // WORD    bfReserved1;
        $BITMAPFILEHEADER .= $this->LittleEndian2String(0, 2); // WORD    bfReserved2;
        $BITMAPFILEHEADER .= $this->LittleEndian2String(54, 4); // DWORD   bfOffBits;

        // BITMAPINFOHEADER - [40 bytes] http://msdn.microsoft.com/library/en-us/gdi/bitmaps_1rw2.asp
        $BITMAPINFOHEADER = $this->LittleEndian2String(40, 4); // DWORD  biSize;
        $BITMAPINFOHEADER .= $this->LittleEndian2String($imageX, 4); // LONG   biWidth;
        $BITMAPINFOHEADER .= $this->LittleEndian2String($imageY, 4); // LONG   biHeight;
        $BITMAPINFOHEADER .= $this->LittleEndian2String(1, 2); // WORD   biPlanes;
        $BITMAPINFOHEADER .= $this->LittleEndian2String(24, 2); // WORD   biBitCount;
        $BITMAPINFOHEADER .= $this->LittleEndian2String(0, 4); // DWORD  biCompression;
        $BITMAPINFOHEADER .= $this->LittleEndian2String(0, 4); // DWORD  biSizeImage;
        $BITMAPINFOHEADER .= $this->LittleEndian2String(2835, 4); // LONG   biXPelsPerMeter;
        $BITMAPINFOHEADER .= $this->LittleEndian2String(2835, 4); // LONG   biYPelsPerMeter;
        $BITMAPINFOHEADER .= $this->LittleEndian2String(0, 4); // DWORD  biClrUsed;
        $BITMAPINFOHEADER .= $this->LittleEndian2String(0, 4); // DWORD  biClrImportant;

        return $BITMAPFILEHEADER . $BITMAPINFOHEADER . $BMP;
    }

    ## --------------------------------------------------------

    /**
     * @param $img
     * @param $x
     * @param $y
     * @return array|bool|false
     */
    private function GetPixelColor($img, $x, $y)
    {
        if (!is_resource($img)) {
            return false;
        }

        return @imagecolorsforindex($img, @imagecolorat($img, $x, $y));
    }

    ## --------------------------------------------------------

    /**
     * @param     $number
     * @param int $minbytes
     * @return string
     */
    private function LittleEndian2String($number, $minbytes = 1)
    {
        $intstring = '';
        while ($number > 0) {
            $intstring = $intstring . chr($number & 255);
            $number >>= 8;
        }

        return str_pad($intstring, $minbytes, "\x00", STR_PAD_RIGHT);
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        BMP SUPPORT (READING)
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param $filename
     * @return bool|false|resource
     */
    private function ImageCreateFromBMP($filename)
    {
        //Ouverture du fichier en mode binaire
        if (!$f1 = fopen($filename, 'rb')) {
            return false;
        }

        //1 : Chargement des ent�tes FICHIER
        $FILE = unpack('vfile_type/Vfile_size/Vreserved/Vbitmap_offset', fread($f1, 14));
        if (19778 != $FILE['file_type']) {
            return false;
        }

        //2 : Chargement des ent�tes BMP
        $BMP = unpack(
            'Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' . '/Vcompression/Vsize_bitmap/Vhoriz_resolution' . '/Vvert_resolution/Vcolors_used/Vcolors_important',
            fread($f1, 40)
        );
        $BMP['colors'] = pow(2, $BMP['bits_per_pixel']);

        if (0 == $BMP['size_bitmap']) {
            $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
        }

        $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel'] / 8;
        $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
        $BMP['decal'] = ($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] -= floor($BMP['width'] * $BMP['bytes_per_pixel'] / 4);
        $BMP['decal'] = 4 - (4 * $BMP['decal']);

        if (4 == $BMP['decal']) {
            $BMP['decal'] = 0;
        }

        //3 : Chargement des couleurs de la palette
        $PALETTE = [];
        if ($BMP['colors'] < 16777216) {
            $PALETTE = unpack('V' . $BMP['colors'], fread($f1, $BMP['colors'] * 4));
        }

        //4 : Cr�ation de l'image
        $IMG = fread($f1, $BMP['size_bitmap']);
        $VIDE = chr(0);

        $res = imagecreatetruecolor($BMP['width'], $BMP['height']);
        $P = 0;
        $Y = $BMP['height'] - 1;
        while ($Y >= 0) {
            $X = 0;
            while ($X < $BMP['width']) {
                if (24 == $BMP['bits_per_pixel']) {
                    $COLOR = unpack('V', mb_substr($IMG, $P, 3) . $VIDE);
                } elseif (16 == $BMP['bits_per_pixel']) {
                    /*
                     * BMP 16bit fix
                     * =================
                     *
                     * Ref: http://us3.php.net/manual/en/function.imagecreate.php#81604
                     *
                     * Notes:
                     * "don't work with bmp 16 bits_per_pixel. change pixel
                     * generator for this."
                     *
                     */

                    // *** Original code (don't work)
                    //$COLOR = unpack("n",substr($IMG,$P,2));
                    //$COLOR[1] = $PALETTE[$COLOR[1]+1];

                    $COLOR = unpack('v', mb_substr($IMG, $P, 2));
                    $blue = ($COLOR[1] & 0x001f) << 3;
                    $green = ($COLOR[1] & 0x07e0) >> 3;
                    $red = ($COLOR[1] & 0xf800) >> 8;
                    $COLOR[1] = $red * 65536 + $green * 256 + $blue;
                } elseif (8 == $BMP['bits_per_pixel']) {
                    $COLOR = unpack('n', $VIDE . mb_substr($IMG, $P, 1));
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif (4 == $BMP['bits_per_pixel']) {
                    $COLOR = unpack('n', $VIDE . mb_substr($IMG, floor($P), 1));
                    if (0 == ($P * 2) % 2) {
                        $COLOR[1] = ($COLOR[1] >> 4);
                    } else {
                        $COLOR[1] = ($COLOR[1] & 0x0F);
                    }
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } elseif (1 == $BMP['bits_per_pixel']) {
                    $COLOR = unpack('n', $VIDE . mb_substr($IMG, floor($P), 1));
                    if (0 == ($P * 8) % 8) {
                        $COLOR[1] = $COLOR[1] >> 7;
                    } elseif (1 == ($P * 8) % 8) {
                        $COLOR[1] = ($COLOR[1] & 0x40) >> 6;
                    } elseif (2 == ($P * 8) % 8) {
                        $COLOR[1] = ($COLOR[1] & 0x20) >> 5;
                    } elseif (3 == ($P * 8) % 8) {
                        $COLOR[1] = ($COLOR[1] & 0x10) >> 4;
                    } elseif (4 == ($P * 8) % 8) {
                        $COLOR[1] = ($COLOR[1] & 0x8) >> 3;
                    } elseif (5 == ($P * 8) % 8) {
                        $COLOR[1] = ($COLOR[1] & 0x4) >> 2;
                    } elseif (6 == ($P * 8) % 8) {
                        $COLOR[1] = ($COLOR[1] & 0x2) >> 1;
                    } elseif (7 == ($P * 8) % 8) {
                        $COLOR[1] = ($COLOR[1] & 0x1);
                    }
                    $COLOR[1] = $PALETTE[$COLOR[1] + 1];
                } else {
                    return false;
                }

                imagesetpixel($res, $X, $Y, $COLOR[1]);
                ++$X;
                $P += $BMP['bytes_per_pixel'];
            }

            $Y--;
            $P += $BMP['decal'];
        }

        //Fermeture du fichier
        fclose($f1);

        return $res;
    }

    /*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*-
        PSD SUPPORT (READING)
    *-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-*-**-*-*-*-*-*-*-*-*-*-*-*-*-*/

    /**
     * @param $fileName
     * @return bool|false|resource|string
     */
    private function imagecreatefrompsd($fileName)
    {
        if (file_exists($this->psdReaderPath)) {
            include_once($this->psdReaderPath);

            $psdReader = new PhpPsdReader($fileName);

            if (isset($psdReader->infoArray['error'])) {
                return '';
            }

            return $psdReader->getImage();
        }

        return false;
    }

    ## --------------------------------------------------------

    public function __destruct()
    {
        if (is_resource($this->imageResized)) {
            imagedestroy($this->imageResized);
        }
    }

    ## --------------------------------------------------------
}

/*
 *    Example with some API calls (outdated):
 *
 *
 *      ===============================
 *      Compulsary
 *      ===============================
 *
 *      include("classes/resize_class.php");
 *
 *      // *** Initialise object
 *      $magicianObj = new resize('images/cars/large/a.jpg');
 *
 *      // *** Turn off stretching (optional)
 *      $magicianObj -> setForceStretch(false);
 *
 *      // *** Resize object
 *      $magicianObj -> resizeImage(150, 100, 0);
 *
 *      ===============================
 *      Image options - can run none, one, or all.
 *      ===============================
 *
 *      //  *** Add watermark
 *        $magicianObj -> addWatermark('stamp.png');
 *
 *          // *** Add text
 *      $magicianObj -> addText('testing...');
 *
 *      ===============================
 *      Output options - can run one, or the other, or both.
 *      ===============================
 *
 *      // *** Save image to disk
 *      $magicianObj -> saveImage('images/cars/large/b.jpg', 100);
 *
 *          // *** Or output to screen (params in can be jpg, gif, png)
 *      $magicianObj -> displayImage('png');
 *
 *      ===============================
 *      Return options - return errors. nice for debuggin.
 *      ===============================
 *
 *      // *** Return error array
 *      $errorArray = $magicianObj -> getErrors();
 *
 *
 *      ===============================
 *      Cleanup options - not really neccessary, but good practice
 *      ===============================
 *
 *      // *** Free used memory
 *      $magicianObj -> __destruct();
 */
