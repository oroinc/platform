<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Async\Extension\Stub;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use ProxyManager\Proxy\LazyLoadingInterface;

/**
 * This class is needed to avoid exceptions with default ConfigManager class.
 */
class ConfigManagerProxyStub extends ConfigManager implements LazyLoadingInterface
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
