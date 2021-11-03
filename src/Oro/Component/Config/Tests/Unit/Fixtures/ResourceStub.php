<?php

namespace Oro\Component\Config\Tests\Unit\Fixtures;

use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;

class ResourceStub implements SelfCheckingResourceInterface
{
    /** @var bool */
    private $fresh = true;

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return 'stub';
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
