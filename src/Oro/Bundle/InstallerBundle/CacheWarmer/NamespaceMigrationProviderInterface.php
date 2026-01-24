<?php

namespace Oro\Bundle\InstallerBundle\CacheWarmer;

/**
 * Defines the contract for providing namespace migration configuration.
 *
 * Implementations of this interface supply configuration data needed to fix database
 * definitions during namespace migrations. This is typically used when entity namespaces
 * change and the database schema needs to be updated to reflect these changes. The
 * configuration returned by implementations guides the migration process in updating
 * database references to renamed or relocated entities.
 */
interface NamespaceMigrationProviderInterface
{
    /**
     * Config changes for fix database defenitions
     *
     * @return array
     */
    public function getConfig();
}
