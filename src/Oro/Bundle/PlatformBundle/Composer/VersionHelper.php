<?php

namespace Oro\Bundle\PlatformBundle\Composer;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\PlatformBundle\OroPlatformBundle;

/**
 * The helper class that can be used to get the version of a package registered in the Composer.
 */
class VersionHelper
{
    private const UNDEFINED_VERSION = 'N/A';

    /** @var LocalRepositoryFactory */
    private $factory;

    /** @var Cache|null */
    private $cache;

    /** @var array */
    private $packageVersions = [];

    public function __construct(LocalRepositoryFactory $factory, Cache $cache = null)
    {
        $this->factory = $factory;
        $this->cache = $cache;
    }

    public function getVersion(string $packageName = OroPlatformBundle::PACKAGE_NAME): string
    {
        if (isset($this->packageVersions[$packageName])) {
            return $this->packageVersions[$packageName];
        }

        if (null === $this->cache) {
            $version = $this->getPackageVersion($packageName);
        } else {
            $version = $this->cache->fetch($packageName);
            if (false === $version) {
                $version = $this->getPackageVersion($packageName);
                $this->cache->save($packageName, $version);
            }
        }

        $this->packageVersions[$packageName] = $version;

        return $version;
    }

    private function getPackageVersion(string $packageName): string
    {
        $packages = $this->factory->getLocalRepository()->findPackages($packageName);
        $package = current($packages);
        if (!$package) {
            return self::UNDEFINED_VERSION;
        }

        return $package->getPrettyVersion();
    }
}
