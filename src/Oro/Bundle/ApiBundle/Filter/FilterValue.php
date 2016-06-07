<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Represents input option that is used to filter data requested by Data API.
 */
class FilterValue
{
    /** @var string */
    protected $path;

    /** @var mixed */
    protected $value;

    /** @var string */
    protected $operator;

    /**
     * @param string      $path
     * @param mixed       $value
     * @param string|null $operator
     */
    public function __construct($path, $value, $operator = null)
    {
        $this->path = $path;
        $this->value = $value;
        $this->operator = $operator;
    }

    /**
     * Gets a path the filter is applied.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Sets a path the filter is applied.
     *
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * Gets a value of a filter.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets a value of a filter.
     *
     * @param mixed $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Gets an operator of a filter.
     *
     * @return string|null
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Sets an operator of a filter.
     *
     * @param string|null $operator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }
}
