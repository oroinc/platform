<?php

namespace Oro\Component\Layout\Tests\Unit\Stubs;

use Oro\Component\Layout\ContextInterface;

class LayoutContextStub implements ContextInterface
{
    /** @var array */
    protected $items = [];

    /** @var boolean */
    protected $resolved = false;

    /**
     * @param array $items
     * @param bool  $resolved
     */
    public function __construct(array $items, $resolved = false)
    {
        $this->items = $items;
        $this->resolved = $resolved;
    }

    /**
     * {@inheritdoc}
     */
    public function getResolver()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function resolve()
    {
        $this->resolved = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isResolved()
    {
        return $this->resolved;
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        return array_key_exists($name, $this->items);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        return $this->getOr($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getOr($name, $default = null)
    {
        if ($this->has($name)) {
            return $this->items[$name];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $value)
    {
        $this->items[$name] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($name)
    {
        if ($this->has($name)) {
            unset($this->items[$name]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function data()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($name)
    {
        return $this->has($name);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($name)
    {
        return $this->getOr($name);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($name)
    {
        $this->remove($name);
    }


    /**
     * {@inheritdoc}
     */
    public function getHash()
    {
        return md5(serialize($this->items));
    }
}
