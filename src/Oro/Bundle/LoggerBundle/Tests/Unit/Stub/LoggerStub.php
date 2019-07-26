<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Stub;

use ProxyManager\Proxy\LazyLoadingInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * This class is needed to avoid exceptions with default Logger interface.
 */
class LoggerStub extends Logger implements LazyLoadingInterface
{
    /**
     * {@inheritdoc}
     */
    public function setProxyInitializer(\Closure $initializer = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getProxyInitializer()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function initializeProxy(): bool
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isProxyInitialized(): bool
    {
    }
}
