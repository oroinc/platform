<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Represents input option that is used to filter data requested by Data API.
 */
class FilterValue
{
    /** @var string */
    private $path;

    /** @var mixed */
    private $value;

    /** @var string */
    private $operator;

    /** @var string|null */
    private $sourceKey;

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

    /**
     * Gets a key this value was come from a request.
     * E.g. it can be URI query parameter for REST API filters.
     *
     * @return string|null
     */
    public function getSourceKey()
    {
        return $this->sourceKey;
    }

    /**
     * Sets a key this value was come from a request.
     * E.g. it can be URI query parameter for REST API filters.
     *
     * @param string $sourceKey
     */
    public function setSourceKey($sourceKey)
    {
        $this->sourceKey = $sourceKey;
    }
}
