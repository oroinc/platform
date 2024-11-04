<?php

namespace Oro\Component\ChainProcessor\Tests\Unit;

use Oro\Component\ChainProcessor\ToArrayInterface;

class TestArrayObject implements ToArrayInterface
{
    private array $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    #[\Override]
    public function toArray(): array
    {
        return $this->items;
    }
}
