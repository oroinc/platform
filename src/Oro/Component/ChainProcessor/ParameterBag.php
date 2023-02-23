<?php

namespace Oro\Component\ChainProcessor;

/**
 * The container for key/value pairs.
 */
class ParameterBag extends AbstractParameterBag
{
    /** @var array [key => value, ...] */
    private array $items = [];

    /**
     * {@inheritDoc}
     */
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->items);
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key): mixed
    {
        return $this->items[$key] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, mixed $value): void
    {
        $this->items[$key] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $key): void
    {
        unset($this->items[$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * {@inheritDoc}
     */
    public function clear(): void
    {
        $this->items = [];
    }

    /**
     * {@inheritDoc}
     */
    public function count(): int
    {
        return \count($this->items);
    }
}
