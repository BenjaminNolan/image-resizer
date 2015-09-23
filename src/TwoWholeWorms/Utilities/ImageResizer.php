<?php

/**
 * Image Resizer
 *
 * This class provides a nice interface to PHP's GD library and handles a bunch of stuff nicely
 *
 * @link      https://github.com/TwoWholeWorms/image-resizer
 * @copyright Copyright (c) 2015 Benjamin Nolan. (http://ben.mu)
 * @license   MIT
 */

namespace TwoWholeWorms\Utilities;

use TwoWholeWorms\Exceptions\ImageResizerInvalidValueException;
use TwoWholeWorms\Exceptions\ImageResizerRuntimeException;

class ImageResizer
{

    /**
     * Crop the resized image so that it covers the target space at the original aspect ratio
     */
    const MODE_COVER = 'cover';

    /**
     * Resize the image keeping its aspect ratio, but shrink it so it fits within the target width and height without cropping
     */
    const MODE_SHRINK = 'shrink';

    /**
     * Resize so it covers the target width and height by stretching the image to fit
     */
    const MODE_STRETCH = 'stretch';

    protected $config = [
        'format' => IMAGETYPE_PNG,
        'mode' => self::MODE_COVER,
        'height' => 200,
        'width' => 300,
    ];

    protected $sourceImage;
    protected $destImage;

    protected $sourceImageInfo;

    /**
     * @param array $config An array of configuration variables passed in on instantiation
     */
    public function __construct(array $config = null)
    {
        if (null !== $config) {
            if (isset($config['format'])) $this->setFormat($config['format']);
            if (isset($config['mode']))   $this->setMode($config['mode']);

            if (isset($config['height'])) $this->setHeight($config['height']);
            if (isset($config['width']))  $this->setWidth($config['width']);
        }
    }

    /**
     * Sets the target image format
     */
    public function setFormat($format)
    {
        $formats = [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG];
        if (!in_array($format, $formats)) {
            throw new ImageResizerInvalidValueException('Invalid format `' . print_r($format, true) . '` passed; must be one of: ' . implode(', ', $formats));
        }
        $this->config['format'] = $format;

        return $this;
    }

    /**
     * Sets the mode used to calculate how to do the actual resizing
     */
    public function setMode($mode)
    {
        $modes = [self::MODE_COVER, self::MODE_SHRINK, self::MODE_STRETCH];
        if (!in_array($mode, $modes)) {
            throw new ImageResizerInvalidValueException('Invalid mode `' . print_r($mode, true) . '` passed; must be one of: ' . implode(', ', $modes));
        }
        $this->config['mode'] = $mode;

        return $this;
    }

    public function setWidth($width)
    {
        if (!is_integer($width) || 1 > $width) {
            throw new ImageResizerInvalidValueException('Invalid width `' . print_r($width, true) . '` passed; must be an integer > 0');
        }
        $this->config['width'] = $width;

        return $this;
    }

    public function setHeight($height)
    {
        if (!is_integer($height) || 1 > $height) {
            throw new ImageResizerInvalidValueException('Invalid height `' . print_r($height, true) . '` passed; must be an integer > 0');
        }
        $this->config['width'] = $width;

        return $this;
    }

    public function loadImage($fileOrUri)
    {
        // Tells Dropbox to just feed us the file rather than its gallery page
		if (preg_match('#^https?://(www.)?dropbox.com/.*\?dl=0#', $fileOrUri)) {
			$fileOrUri = preg_replace('#\?dl=0#', '?dl=1', $fileOrUri);
		}

		$this->sourceImageInfo = getimagesize($fileOrUri);
		if (!$this->sourceImageInfo) {
			throw new ImageResizerInvalidValueException('Unable to get image info for `' . print_r($fileOrUri, true) . '`');
		} elseif (!in_array($this->sourceImageInfo[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF])) {
			throw new ImageResizerInvalidValueException('Unknown image type `' . $this->sourceImageInfo[2] . '` provided. (Perhaps it\'s new and needs adding to the library?)');
		} else {
			switch ($this->sourceImageInfo[2]) {
				case IMAGETYPE_GIF:
					$this->sourceImage = imagecreatefromgif($fileOrUri);
					break;

				case IMAGETYPE_JPEG:
					$this->sourceImage = imagecreatefromjpeg($fileOrUri);
					break;

				case IMAGETYPE_PNG:
					$this->sourceImage = imagecreatefrompng($fileOrUri);
					break;

                default:
                    throw new ImageResizerRuntimeException('Unknown image type `' . print_r($this->sourceImageInfo[2], true) . '`.');
                    break;
			}
        }
    }

    public function process(array $overrides = null)
    {
        // Calculate the aspect ratios of the source image and the target dimensions
        $sourceImageAspectRatio = $this->sourceImageInfo[0] / $this->sourceImageInfo[1];
        $destImageAspectRatio = $this->width / $this->height;

        $config = array_merge($this->config, $overrides);

        $sourceX = 0;
        $sourceY = 0;
        $sourceWidth = $this->sourceImageInfo[0];
        $sourceHeight = $this->sourceImageInfo[1];

        $destX = 0;
        $destY = 0;
        $destWidth = 0;
        $destHeight = 0;

        switch ($config['mode']) {
            case self::MODE_COVER:
                /*
                 * If the source image's aspect ratio is higher than the destination dimensions (ie, it's wider), calculate
                 * the new width from the destination height and then crop the image to the destination dimensions.
                 */
                if ($sourceImageAspectRatio > $destImageAspectRatio) {
                    $destHeight = $config['height'];
                    $sourceX = (int)((($destHeight * $sourceImageAspectRatio) - $config['width']) / 2);
                } else {
                    $destWidth = $config['width'];
                    $sourceY = (int)((($destWidth / $sourceImageAspectRatio) - $config['height']) / 2);
                }
                break;

            case self::MODE_SHRINK:
                /*
                 * If the source image's aspect ratio is higher than the destination dimensions (ie, it's wider), calculate
                 * the new width from the destination height
                 */
                if ($sourceImageAspectRatio > $destImageAspectRatio) {
                    $destHeight = $config['height'];
                    $destWidth = (int)($destHeight * $sourceImageAspectRatio);
                } else {
                    $destWidth = $config['width'];
                    $destHeight = (int)($destWidth / $sourceImageAspectRatio);
                }
                break;

            case self::MODE_STRETCH:
                // Here, simply set the new width and height
                $destHeight = $config['height'];
                $destWidth = $config['width'];
                break;

            default:
                throw new ImageResizerRuntimeException('Unknown processing mode `' . print_r($config['mode'], true) . '` requested.');
                break;
        }

        $this->destImage = imagecreatetruecolor($destWidth, $destHeight);
		imagecopyresampled(
			$this->destImage,
			$this->sourceImage,
			$destX, $destY,
			$sourceX, $sourceY,
			$destWidth, $destHeight,
			$sourceWidth, $sourceHeight
		);
    }

    public function save($saveToLocation, array $overrides = null)
    {
        if (!$this->destImage || $overrides) {
            $this->process($overrides);
        }

        switch ($this->format) {
            case IMAGETYPE_PNG:
                imagepng($this->destImage, $saveToLocation);
                break;

            case IMAGETYPE_GIF:
                imagegif($this->destImage, $saveToLocation);
                break;

            case IMAGETYPE_JPEG:
                imagejpeg($this->destImage, $saveToLocation);
                break;

            default:
                throw new ImageResizerRuntimeException('Unknown destination image format `' . print_r($config['format'], true) . '` requested.');
                break;
        }
    }

}
