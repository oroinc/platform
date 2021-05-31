<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Async\Extension\Stub;

use ProxyManager\Proxy\LazyLoadingInterface;
use Symfony\Component\ErrorHandler\BufferingLogger;

/**
 * This class is needed to avoid exceptions with default Logger interface.
 */
class LoggerProxyStub extends BufferingLogger implements LazyLoadingInterface
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
    public function getProxyInitializer(): ?\Closure
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
