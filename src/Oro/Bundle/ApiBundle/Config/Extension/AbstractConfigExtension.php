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
    public function getEntityConfigurationSections()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigureCallbacks()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPreProcessCallbacks()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getPostProcessCallbacks()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityConfigurationLoaders()
    {
        return [];
    }
}
