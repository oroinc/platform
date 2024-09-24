<?php

namespace Oro\Component\ChainProcessor;

/**
 * The container for key/value pairs.
 */
class ParameterBag extends AbstractParameterBag
{
    /** @var array [key => value, ...] */
    private array $items = [];

    #[\Override]
    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->items);
    }

    #[\Override]
    public function get(string $key): mixed
    {
        return $this->items[$key] ?? null;
    }

    #[\Override]
    public function set(string $key, mixed $value): void
    {
        $this->items[$key] = $value;
    }

    #[\Override]
    public function remove(string $key): void
    {
        unset($this->items[$key]);
    }

    #[\Override]
    public function toArray(): array
    {
        return $this->items;
    }

    #[\Override]
    public function clear(): void
    {
        $this->items = [];
    }

    #[\Override]
    public function count(): int
    {
        return \count($this->items);
    }
}
