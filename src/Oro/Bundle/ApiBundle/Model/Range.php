<?php

namespace Oro\Bundle\ApiBundle\Model;

/**
 * This class represents a pair of "from" and "to" values.
 */
class Range
{
    private mixed $fromValue;
    private mixed $toValue;

    public function __construct(mixed $fromValue = null, mixed $toValue = null)
    {
        $this->fromValue = $fromValue;
        $this->toValue = $toValue;
    }

    public function getFromValue(): mixed
    {
        return $this->fromValue;
    }

    public function setFromValue(mixed $value): void
    {
        $this->fromValue = $value;
    }

    public function getToValue(): mixed
    {
        return $this->toValue;
    }

    public function setToValue(mixed $value): void
    {
        $this->toValue = $value;
    }
}
