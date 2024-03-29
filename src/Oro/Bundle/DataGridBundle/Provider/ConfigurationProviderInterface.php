<?php

namespace Oro\Bundle\DataGridBundle\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

/**
 * Provides an interface for classes responsible to load datagrid configuration.
 */
interface ConfigurationProviderInterface
{
    /**
     * Checks if this provider can be used to load configuration of a grid with the given name.
     *
     * @param string $gridName The name of a datagrid
     *
     * @return bool
     */
    public function isApplicable(string $gridName): bool;

    /**
     * Returns prepared config for requested datagrid.
     *
     * @param string $gridName The name of a datagrid
     *
     * @return DatagridConfiguration
     *
     * @throws \RuntimeException in case when datagrid configuration not found
     */
    public function getConfiguration(string $gridName): DatagridConfiguration;

    /**
     * Checks is datagrid configuration valid
     *
     * @param string $gridName
     * @return bool
     */
    public function isValidConfiguration(string $gridName): bool;
}
