<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Duplicator\Filter\Stub;

use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Model\ExtendEntityStorage;

class ExtendEntityStub implements ExtendEntityInterface
{
    public ?\ArrayObject $extendEntityStorage = null;

    public function __construct(?\ArrayObject $storage = null)
    {
        $this->extendEntityStorage = $storage;
    }

    #[\Override]
    public function get(string $name): mixed
    {
        return $this->extendEntityStorage?->offsetGet($name);
    }

    #[\Override]
    public function set(string $name, mixed $value): static
    {
        $this->extendEntityStorage ??= new ExtendEntityStorage();
        $this->extendEntityStorage->offsetSet($name, $value);

        return $this;
    }
}
