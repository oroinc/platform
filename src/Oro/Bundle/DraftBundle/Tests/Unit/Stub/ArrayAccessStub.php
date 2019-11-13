<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Stub;

use ArrayAccess;

class ArrayAccessStub implements ArrayAccess
{
    /**
     * @var array
     */
    private $container;

    /**
     * SampleArrayAccessStub constructor.
     */
    public function __construct()
    {
        $this->container = [];
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if (null === $offset) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }

    /**
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return $this->container[$offset] ?? null;
    }
}
