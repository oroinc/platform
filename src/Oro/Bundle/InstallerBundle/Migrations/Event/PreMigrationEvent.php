<?php

namespace Oro\Bundle\InstallerBundle\Migrations\Event;

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
