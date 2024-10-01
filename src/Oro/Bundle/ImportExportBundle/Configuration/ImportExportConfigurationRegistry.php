<?php

namespace Oro\Bundle\ImportExportBundle\Configuration;

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
