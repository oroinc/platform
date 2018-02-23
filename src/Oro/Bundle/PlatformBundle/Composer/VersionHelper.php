<?php

namespace Oro\Bundle\PlatformBundle\Composer;

use Composer\Package\PackageInterface;
use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\PlatformBundle\OroPlatformBundle;

class VersionHelper
{
    const UNDEFINED_VERSION = 'N/A';

    /**
     * @var LocalRepositoryFactory
     */
    protected $factory;

    /**
     * @var CacheProvider
     */
    protected $cache;

    /**
     * @var array
     */
    protected $packageVersions = [];

    /**
     * @param LocalRepositoryFactory $factory
     */
    public function __construct(LocalRepositoryFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Set cache instance
     *
     * @param CacheProvider $cache
     */
    public function setCache(CacheProvider $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $packageName
     * @return string
     */
    public function getVersion($packageName = OroPlatformBundle::PACKAGE_NAME)
    {
        // Get package version from local cache if any
        if (isset($this->packageVersions[$packageName])) {
            return $this->packageVersions[$packageName];
        }

        // Try to get package version from persistent cache
        if ($this->cache && $this->cache->contains($packageName)) {
            $version = $this->cache->fetch($packageName);
        } else {
            // Get package version from composer repository
            $packages = $this->factory->getLocalRepository()->findPackages($packageName);

            if ($package = current($packages)) {
                /** @var PackageInterface $package */
                $version = $package->getPrettyVersion();
            } else {
                $version = self::UNDEFINED_VERSION;
            }

            //Save package version to persistent cache
            if ($this->cache) {
                $this->cache->save($packageName, $version);
            }
        }

        // Save package version to local cache
        $this->packageVersions[$packageName] = $version;

        return $version;
    }
}
