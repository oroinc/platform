<?php

namespace Oro\Bundle\ImportExportBundle\Configuration;

/**
 * Defines the contract for managing import/export configurations.
 *
 * This registry interface provides access to import/export configurations registered
 * for specific entity aliases. Implementations maintain a collection of configurations
 * that define how entities should be imported and exported, including field mappings,
 * validation rules, and other import/export-specific settings.
 */
interface ImportExportConfigurationRegistryInterface
{
    /**
     * @param string $alias
     *
     * @return ImportExportConfigurationInterface[]
     */
    public function getConfigurations(string $alias): array;
}
