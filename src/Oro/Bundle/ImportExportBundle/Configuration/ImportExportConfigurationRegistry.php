<?php

namespace Oro\Bundle\ImportExportBundle\Configuration;

/**
 * Manages a registry of import/export configurations.
 *
 * This class stores and retrieves configurations that have been registered via configuration
 * providers. It maintains configurations organized by alias, allowing efficient lookup of all
 * configurations associated with a specific alias. This is typically used during dependency
 * injection compilation to aggregate configurations from multiple providers.
 */
class ImportExportConfigurationRegistry implements ImportExportConfigurationRegistryInterface
{
    /**
     * @var array
     */
    private $configurations = [];

    public function addConfiguration(ImportExportConfigurationProviderInterface $provider, string $alias)
    {
        $this->configurations[$alias][] = $provider->get();
    }

    #[\Override]
    public function getConfigurations(string $alias): array
    {
        if (!array_key_exists($alias, $this->configurations)) {
            return [];
        }

        return $this->configurations[$alias];
    }
}
