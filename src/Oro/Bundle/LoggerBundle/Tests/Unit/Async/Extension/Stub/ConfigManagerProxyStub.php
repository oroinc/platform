<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Async\Extension\Stub;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use ProxyManager\Proxy\LazyLoadingInterface;

/**
 * This class is needed to avoid exceptions with default ConfigManager class.
 */
class ConfigManagerProxyStub extends ConfigManager implements LazyLoadingInterface
{
    #[\Override]
    public function setProxyInitializer(\Closure $initializer = null)
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
