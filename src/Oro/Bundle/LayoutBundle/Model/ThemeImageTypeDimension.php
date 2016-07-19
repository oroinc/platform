<?php

namespace Oro\Bundle\LayoutBundle\Model;

class ThemeImageTypeDimension
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    private $height;

    /**
     * @param string $name
     * @param int|null $width
     * @param int|null $height
     */
    public function __construct($name, $width, $height)
    {
        $this->name = $name;
        $this->width = $width;
        $this->height = $height;
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
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }
}
