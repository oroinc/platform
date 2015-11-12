<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Represents an value/operator pair that is used to filter requested by API data.
 */
class FilterValue
{
    /** @var mixed */
    protected $value;

    /** @var string */
    protected $operator;

    /**
     * @param mixed  $value
     * @param string $operator
     */
    public function __construct($value, $operator)
    {
        $this->value    = $value;
        $this->operator = $operator;
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
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Sets an operator of a filter.
     *
     * @param string $operator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }
}
