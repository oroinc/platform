<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Stub;

use Closure;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use ProxyManager\Proxy\LazyLoadingInterface;

/**
 * This class is needed to avoid exceptions with default ConfigManager class.
 */
class ConfigManagerStub extends ConfigManager implements LazyLoadingInterface
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
