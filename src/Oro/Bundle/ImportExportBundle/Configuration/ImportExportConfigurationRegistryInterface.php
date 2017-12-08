<?php

namespace Oro\Bundle\ImportExportBundle\Configuration;

interface ImportExportConfigurationRegistryInterface
{
    /**
     * @param string $alias
     *
     * @return ImportExportConfigurationInterface[]
     */
    public function getConfigurations(string $alias): array;
}
