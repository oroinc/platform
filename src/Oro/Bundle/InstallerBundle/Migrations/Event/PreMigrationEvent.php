<?php

namespace Oro\Bundle\InstallerBundle\Migrations\Event;

class PreMigrationEvent extends MigrationEvent
{
    protected $loadedVersions = [];

    public function getLoadedVersions()
    {
        return $this->loadedVersions;
    }

    public function getLoadedVersion($bundleName)
    {
        return isset($this->loadedVersions[$bundleName])
            ? $this->loadedVersions[$bundleName]
            : null;
    }

    public function setLoadedVersion($bundleName, $version)
    {
        $this->loadedVersions[$bundleName] = $version;
    }
}
