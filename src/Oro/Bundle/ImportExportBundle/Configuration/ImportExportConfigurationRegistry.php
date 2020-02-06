<?php

namespace Oro\Bundle\ImportExportBundle\Configuration;

/**
 * Import/Export configurations registry.
 */
class ImportExportConfigurationRegistry implements ImportExportConfigurationRegistryInterface
{
    /**
     * @var array
     */
    private $configurations = [];

    /**
     * {@inheritDoc}
     */
    public function addConfiguration(ImportExportConfigurationProviderInterface $provider, string $alias)
    {
        $this->configurations[$alias][] = $provider;
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigurations(string $alias): array
    {
        if (!array_key_exists($alias, $this->configurations)) {
            return [];
        }

        return array_map(
            static function (ImportExportConfigurationProviderInterface $provider) {
                return $provider->get();
            },
            $this->configurations[$alias]
        );
    }
}
