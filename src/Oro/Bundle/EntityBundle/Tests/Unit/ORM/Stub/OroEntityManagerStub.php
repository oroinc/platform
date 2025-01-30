<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub;

use Closure;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use ProxyManager\Proxy\LazyLoadingInterface;

class OroEntityManagerStub extends OroEntityManager implements LazyLoadingInterface
{
    /** @var \Closure */
    private $proxyInitializer;

    #[\Override]
    public function setProxyInitializer(?\Closure $initializer = null)
    {
        $this->proxyInitializer = $initializer;
    }

    #[\Override]
    public function getProxyInitializer(): ?Closure
    {
        return $this->proxyInitializer;
    }

    #[\Override]
    public function initializeProxy(): bool
    {
        return true;
    }

    #[\Override]
    public function isProxyInitialized(): bool
    {
        return true;
    }
}
