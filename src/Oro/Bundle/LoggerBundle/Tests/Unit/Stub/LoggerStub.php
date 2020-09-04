<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Stub;

use Closure;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use ProxyManager\Proxy\LazyLoadingInterface;

/**
 * This class is needed to avoid exceptions with default Logger interface.
 */
class LoggerStub extends ArrayLogger implements LazyLoadingInterface
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
    public function getProxyInitializer() : ?Closure
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
