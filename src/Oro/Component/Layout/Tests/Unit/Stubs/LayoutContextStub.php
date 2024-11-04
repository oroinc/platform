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

    #[\Override]
    public function getResolver()
    {
    }

    #[\Override]
    public function resolve()
    {
        $this->resolved = true;
    }

    #[\Override]
    public function isResolved()
    {
        return $this->resolved;
    }

    #[\Override]
    public function has($name)
    {
        return array_key_exists($name, $this->items);
    }

    #[\Override]
    public function get($name)
    {
        return $this->getOr($name);
    }

    #[\Override]
    public function getOr($name, $default = null)
    {
        if ($this->has($name)) {
            return $this->items[$name];
        }

        return $default;
    }

    #[\Override]
    public function set($name, $value)
    {
        $this->items[$name] = $value;
    }

    #[\Override]
    public function remove($name)
    {
        if ($this->has($name)) {
            unset($this->items[$name]);
        }
    }

    #[\Override]
    public function data()
    {
        return [];
    }

    #[\Override]
    public function offsetExists($name): bool
    {
        return $this->has($name);
    }

    #[\Override]
    public function offsetGet($name): mixed
    {
        return $this->getOr($name);
    }

    #[\Override]
    public function offsetSet($name, $value): void
    {
        $this->set($name, $value);
    }

    #[\Override]
    public function offsetUnset($name): void
    {
        $this->remove($name);
    }

    #[\Override]
    public function getHash()
    {
        return md5(serialize($this->items));
    }
}
