<?php

namespace Oro\Bundle\AttachmentBundle\Configurator\Provider;

/**
 * Aggregates multiple runtime configuration providers and merges their configurations
 * for LiipImagine image filters.
 */
class RuntimeConfigurationProvider
{
    public function __construct(private iterable $providers)
    {
    }

    public function getRuntimeConfig(string $filter, array $context = []): array
    {
        $config = [];
        foreach ($this->providers as $provider) {
            if (!$provider->isSupported($filter)) {
                continue;
            }

            $providerConfig = $provider->getRuntimeConfig($filter, new RuntimeContext($context));

            $config = array_replace_recursive($config, $providerConfig);
        }

        return $config;
    }
}
