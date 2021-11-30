<?php

namespace Oro\Component\Config\Tests\Unit\Fixtures;

use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;

class ResourceStub implements SelfCheckingResourceInterface
{
    private string $name;

    private bool $fresh;

    public function __construct(string $name = 'stub', bool $fresh = true)
    {
        $this->name = $name;
        $this->fresh = $fresh;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function isFresh($timestamp): bool
    {
        return $this->fresh;
    }

    /**
     * @param bool $isFresh
     */
    public function setFresh($isFresh): void
    {
        $this->fresh = $isFresh;
    }
}
