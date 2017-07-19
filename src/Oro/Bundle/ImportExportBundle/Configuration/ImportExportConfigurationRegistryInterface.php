<?php

namespace Oro\Bundle\ImportExportBundle\Configuration;

interface ImportExportConfigurationRegistryInterface
{
    /**
     * @param ImportExportConfigurationProviderInterface $provider
     * @param string                                     $alias
     */
    public function addConfiguration(ImportExportConfigurationProviderInterface $provider, string $alias);

    /**
     * @param string $alias
     *
     * @return ImportExportConfigurationInterface[]
     */
    public function getConfiguration(string $alias): array;
}
