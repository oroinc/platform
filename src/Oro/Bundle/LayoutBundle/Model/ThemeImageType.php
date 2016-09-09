<?php

namespace Oro\Bundle\LayoutBundle\Model;

class ThemeImageType
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $label;

    /**
     * @var array
     */
    private $dimensions;

    /**
     * @var int
     */
    private $maxNumber;

    /**
     * @param string $name
     * @param string $label
     * @param array $dimensions
     * @param int|null $maxNumber
     */
    public function __construct($name, $label, array $dimensions, $maxNumber = null)
    {
        $this->name = $name;
        $this->label = $label;
        $this->dimensions = $dimensions;
        $this->maxNumber = $maxNumber;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return array
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * @return int
     */
    public function getMaxNumber()
    {
        return $this->maxNumber;
    }
}
