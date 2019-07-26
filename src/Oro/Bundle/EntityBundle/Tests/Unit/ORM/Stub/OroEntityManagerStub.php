<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use ProxyManager\Proxy\LazyLoadingInterface;

class OroEntityManagerStub extends OroEntityManager implements LazyLoadingInterface
{
    /** @var \Closure */
    private $proxyInitializer;

    /**
     * {@inheritdoc}
     */
    public function setProxyInitializer(\Closure $initializer = null)
    {
        $this->proxyInitializer = $initializer;
    }

    /**
     * {@inheritdoc}
     */
    public function getProxyInitializer()
    {
        return $this->proxyInitializer;
    }

    /**
     * {@inheritdoc}
     */
    public function initializeProxy(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isProxyInitialized(): bool
    {
        return true;
    }
}
