<?php

namespace Oro\Component\Routing\Tests\Unit\Resolver;

use Symfony\Component\Config\Resource\ResourceInterface;
use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;

class TestResource implements ResourceInterface, SelfCheckingResourceInterface
{
    /** @var string */
    protected $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    #[\Override]
    public function __toString(): string
    {
        return (string)$this->name;
    }

    #[\Override]
    public function isFresh($timestamp): bool
    {
        return true;
    }

    public function getResource()
    {
        return $this->name;
    }
}
