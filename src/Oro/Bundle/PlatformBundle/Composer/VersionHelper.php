<?php

namespace Oro\Bundle\PlatformBundle\Composer;

use Composer\Package\PackageInterface;

use Oro\Bundle\PlatformBundle\OroPlatformBundle;

class VersionHelper
{
    const UNDEFINED_VERSION = 'N/A';

    /**
     * @var LocalRepositoryFactory
     */
    protected $factory;

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
     * @param string $packageName
     * @return string
     */
    public function getVersion($packageName = OroPlatformBundle::PACKAGE_NAME)
    {
        if (isset($this->packageVersions[$packageName])) {
            return $this->packageVersions[$packageName];
        }

        $packages = $this->factory->getLocalRepository()->findPackages($packageName);

        if ($package = current($packages)) {
            /** @var PackageInterface $package */
            $version = $package->getPrettyVersion();

            $this->packageVersions[$packageName] = $version;

            return $version;
        }

        $this->packageVersions[$packageName] = self::UNDEFINED_VERSION;

        return self::UNDEFINED_VERSION;
    }
}
