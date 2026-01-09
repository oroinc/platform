<?php

namespace Oro\Bundle\LayoutBundle\Model;

/**
 * Represents a theme image type with associated dimensions and constraints.
 *
 * This model encapsulates the configuration for a specific image type used in a theme,
 * including its name, display label, supported dimensions, and optional maximum number
 * of images allowed. It is used to define and validate image uploads and processing
 * in theme-aware image handling.
 */
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
     * @param ThemeImageTypeDimension[] $dimensions
     * @param int|null $maxNumber
     */
    public function __construct($name, $label, array $dimensions, $maxNumber = null)
    {
        $this->name = $name;
        $this->label = $label;
        $this->maxNumber = $maxNumber;

        foreach ($dimensions as $dimension) {
            $this->addDimension($dimension);
        }
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
     * @return ThemeImageTypeDimension[]
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

    /**
     * @param ThemeImageTypeDimension[] $dimensions
     */
    public function mergeDimensions(array $dimensions)
    {
        foreach ($dimensions as $dimension) {
            $this->addDimension($dimension);
        }
    }

    private function addDimension(ThemeImageTypeDimension $dimension)
    {
        $this->dimensions[$dimension->getName()] = $dimension;
    }
}
