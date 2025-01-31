<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Async\Extension\Stub;

use Oro\Component\Testing\Logger\BufferingLogger;
use ProxyManager\Proxy\LazyLoadingInterface;

/**
 * This class is needed to avoid exceptions with default Logger interface.
 */
class LoggerProxyStub extends BufferingLogger implements LazyLoadingInterface
{
    #[\Override]
    public function setProxyInitializer(?\Closure $initializer = null)
    {
    }

    #[\Override]
    public function getProxyInitializer(): ?\Closure
    {
    }

    #[\Override]
    public function initializeProxy(): bool
    {
    }

    #[\Override]
    public function isProxyInitialized(): bool
    {
    }
}
