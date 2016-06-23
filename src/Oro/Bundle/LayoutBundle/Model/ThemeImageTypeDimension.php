<?php

namespace Oro\Bundle\LayoutBundle\Model;

class ThemeImageTypeDimension
{
    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * @param int $width
     * @param int $height
     */
    public function __construct($width, $height)
    {
        $this->width = (int) $width;
        $this->height = (int) $height;
    }

    /**
     * @return string
     */
    function __toString()
    {
        return sprintf('%d_%d', $this->width, $this->height);
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
}
