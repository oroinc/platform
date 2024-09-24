<?php

namespace Oro\Component\Layout\Extension\Theme\Model;

/**
 * Recursively iterates over layout update files
 */
class ResourceIterator implements \Iterator
{
    /** @var int */
    protected $currentKey;

    /** @var \RecursiveIteratorIterator */
    protected $iterator;

    public function __construct(array $resources)
    {
        $this->iterator = new \RecursiveIteratorIterator(
            new \RecursiveArrayIterator($resources),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
    }

    #[\Override]
    public function current(): mixed
    {
        return $this->iterator->current();
    }

    #[\Override]
    public function key(): int
    {
        return $this->currentKey;
    }

    #[\Override]
    public function valid(): bool
    {
        return $this->iterator->valid();
    }

    #[\Override]
    public function next(): void
    {
        $this->currentKey++;
        $this->iterator->next();
    }

    #[\Override]
    public function rewind(): void
    {
        $this->currentKey = 0;
        $this->iterator->rewind();
    }
}
