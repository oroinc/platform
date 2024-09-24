<?php

namespace Oro\Bundle\ApiBundle\Config\Extension;

/**
 * The base class for API configuration extensions.
 */
abstract class AbstractConfigExtension implements ConfigExtensionInterface
{
    #[\Override]
    public function getEntityConfigurationSections(): array
    {
        return [];
    }

    #[\Override]
    public function getConfigureCallbacks(): array
    {
        return [];
    }

    #[\Override]
    public function getPreProcessCallbacks(): array
    {
        return [];
    }

    #[\Override]
    public function getPostProcessCallbacks(): array
    {
        return [];
    }

    #[\Override]
    public function getEntityConfigurationLoaders(): array
    {
        return [];
    }
}
