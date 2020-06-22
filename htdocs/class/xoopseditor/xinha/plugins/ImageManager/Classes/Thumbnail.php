<?php
/**
 * Create thumbnails.
 * @author  $Author:ray $
 * @version $Id:Thumbnail.php 709 2007-01-30 23:22:04Z ray $
 * @package ImageManager
 */

require_once('../ImageManager/Classes/Transform.php');

/**
 * Thumbnail creation
 * @author     $Author:ray $
 * @version    $Id:Thumbnail.php 709 2007-01-30 23:22:04Z ray $
 * @package    ImageManager
 * @subpackage Images
 */
class Thumbnail
{
    /**
     * Graphics driver, GD, NetPBM or ImageMagick.
     */
    public $driver;

    /**
     * Thumbnail default width.
     */
    public $width = 96;

    /**
     * Thumbnail default height.
     */
    public $height = 96;

    /**
     * Thumbnail default JPEG quality.
     */
    public $quality = 85;

    /**
     * Thumbnail is proportional
     */
    public $proportional = true;

    /**
     * Default image type is JPEG.
     */
    public $type = 'jpeg';

    /**
     * Create a new Thumbnail instance.
     * @param int $width  thumbnail width
     * @param int $height thumbnail height
     */
    public function __construct($width = 96, $height = 96)
    {
        $this->driver = Image_Transform::factory(IMAGE_CLASS);
        $this->width  = $width;
        $this->height = $height;
    }

    /**
     * Create a thumbnail.
     * @param string $file      the image for the thumbnail
     * @param string $thumbnail if not null, the thumbnail will be saved
     *                          as this parameter value.
     * @return bool true if thumbnail is created, false otherwise
     */
    public function createThumbnail($file, $thumbnail = null)
    {
        if (!is_file($file)) {
            Return false;
        }

        //error_log('Creating Thumbs: '.$file);

        $this->driver->load($file);

        if ($this->proportional) {
            $width  = $this->driver->img_x;
            $height = $this->driver->img_y;

            if ($width > $height) {
                $this->height = intval($this->width / $width * $height);
            } elseif ($height > $width) {
                $this->width = intval($this->height / $height * $width);
            }
        }

        $this->driver->resize($this->width, $this->height);

        if (is_null($thumbnail)) {
            $this->save($file);
        } else {
            $this->save($thumbnail);
        }

        $this->free();

        if (is_file($thumbnail)) {
            Return true;
        } else {
            Return false;
        }
    }

    /**
     * Save the thumbnail file.
     * @param string $file file name to be saved as.
     */
    public function save($file)
    {
        $this->driver->save($file);
    }

    /**
     * Free up the graphic driver resources.
     */
    public function free()
    {
        $this->driver->free();
    }
}


