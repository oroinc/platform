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
        $packages = $this->factory->getLocalRepository()->findPackages($packageName);

        if ($package = current($packages)) {
            /** @var PackageInterface $package */
            return $package->getPrettyVersion();
        }

        return self::UNDEFINED_VERSION;
    }
}
