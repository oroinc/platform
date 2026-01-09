<?php

namespace Oro\Bundle\MigrationBundle\Event;

/**
 * Represents an event dispatched before migrations are executed.
 *
 * This event provides access to loaded migration versions for each bundle and allows listeners
 * to register additional migrations that should be executed. It is used to initialize the migration
 * state and prepare the system for the migration process.
 */
class PreMigrationEvent extends MigrationEvent
{
    /**
     * @var array
     *      key   = bundle name
     *      value = version
     */
    protected $loadedVersions = [];

    /**
     * Gets a list of the latest loaded versions for all bundles
     *
     * @return array
     *      key   = bundle name
     *      value = version
     */
    public function getLoadedVersions()
    {
        return $this->loadedVersions;
    }

    /**
     * Gets the latest version loaded version of the given bundle
     *
     * @param string $bundleName
     * @return string|null
     */
    public function getLoadedVersion($bundleName)
    {
        return isset($this->loadedVersions[$bundleName])
            ? $this->loadedVersions[$bundleName]
            : null;
    }

    /**
     * Sets a number of already loaded version of the given bundle
     *
     * @param string $bundleName
     * @param string $version
     */
    public function setLoadedVersion($bundleName, $version)
    {
        $this->loadedVersions[$bundleName] = $version;
    }
}
