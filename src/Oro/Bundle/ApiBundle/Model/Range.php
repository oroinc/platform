<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * This class represents a pair of "from" and "to" values.
 */
class Range
{
    /** @var mixed */
    private $fromValue;

    /** @var mixed */
    private $toValue;

    /**
     * @param mixed $fromValue
     * @param mixed $toValue
     */
    public function __construct($fromValue = null, $toValue = null)
    {
        $this->fromValue = $fromValue;
        $this->toValue = $toValue;
    }

    /**
     * @return mixed
     */
    public function getFromValue()
    {
        return $this->fromValue;
    }

    /**
     * @param mixed $value
     */
    public function setFromValue($value)
    {
        $this->fromValue = $value;
    }

    /**
     * @return mixed
     */
    public function getToValue()
    {
        return $this->toValue;
    }

    /**
     * @param mixed $value
     */
    public function setToValue($value)
    {
        $this->toValue = $value;
    }
}
