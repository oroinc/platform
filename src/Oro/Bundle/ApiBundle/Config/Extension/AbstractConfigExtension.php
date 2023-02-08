<?php

namespace Oro\Bundle\ApiBundle\Config\Extension;

/**
 * The base class for API configuration extensions.
 */
abstract class AbstractConfigExtension implements ConfigExtensionInterface
{
    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationSections(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigureCallbacks(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPreProcessCallbacks(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPostProcessCallbacks(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationLoaders(): array
    {
        return [];
    }
}
