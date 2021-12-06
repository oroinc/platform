<?php

namespace Oro\Bundle\LayoutBundle\Model;

/**
 * A model holding image dimensions and options.
 */
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

    private array $options;

    /**
     * @param string $name
     * @param int|null $width
     * @param int|null $height
     * @param array $options
     */
    public function __construct($name, $width, $height, array $options = [])
    {
        $this->name = $name;
        $this->width = $width;
        $this->height = $height;
        $this->options = $options;
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

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getOption($name)
    {
        return $this->options[$name] ?? null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasOption($name)
    {
        return array_key_exists($name, $this->options);
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
