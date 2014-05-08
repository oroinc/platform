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
     * @var PackageInterface[]
     */
    protected $packages = [];

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
        if (isset($this->packages[$packageName])) {
            return $this->packages[$packageName];
        }

        $packages = $this->factory->getLocalRepository()->findPackages($packageName);

        if ($package = current($packages)) {
            /** @var PackageInterface $package */
            $version = $package->getPrettyVersion();

            $this->packages[$packageName] = $version;

            return $version;
        }

        return self::UNDEFINED_VERSION;
    }
}
