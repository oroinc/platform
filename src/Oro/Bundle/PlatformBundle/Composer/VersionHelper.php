<?php

namespace Oro\Bundle\PlatformBundle\Composer;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\PlatformBundle\OroPlatformBundle;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The helper class that can be used to get the version of a package registered in the Composer.
 */
class VersionHelper
{
    private const UNDEFINED_VERSION = 'N/A';

    private LocalRepositoryFactory $factory;
    private CacheInterface $cache;
    private array $packageVersions = [];

    public function __construct(LocalRepositoryFactory $factory, CacheInterface $cache)
    {
        $this->factory = $factory;
        $this->cache = $cache;
    }

    public function getVersion(string $packageName = OroPlatformBundle::PACKAGE_NAME): string
    {
        if (isset($this->packageVersions[$packageName])) {
            return $this->packageVersions[$packageName];
        }
        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey($packageName);
        $version = $this->cache->get($cacheKey, function () use ($packageName) {
            return $this->getPackageVersion($packageName);
        });

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
