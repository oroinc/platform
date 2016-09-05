<?php

namespace Oro\Bundle\AttachmentBundle\Model;

use Imagine\Image\ImageInterface;

class Thumbnail
{
    /** @var ImageInterface */
    protected $image;

    /** @var int */
    protected $width;

    /** @var int */
    protected $height;

    /** @var string */
    protected $filter;

    /**
     * @param ImageInterface $image
     * @param int            $width
     * @param int            $height
     * @param string         $filter
     */
    public function __construct(ImageInterface $image, $width, $height, $filter)
    {
        $this->image = $image;
        $this->width = $width;
        $this->height = $height;
        $this->filter = $filter;
    }

    /**
     * @return ImageInterface
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }
}
