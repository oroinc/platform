<?php

namespace Oro\Bundle\PlatformBundle\Provider;

use Composer\Package\PackageInterface;

use Oro\Bundle\PlatformBundle\Composer\LocalRepositoryFactory;

class PackageProvider
{
    const ORO_NAMESPACE = 'oro';
    const NAMESPACE_DELIMITER = '/';

    const PACKAGE_PLATFORM = 'platform';
    const PACKAGE_CRM = 'crm';
    const PACKAGE_COMMERCE = 'commerce';

    /** @var LocalRepositoryFactory */
    protected $localRepositoryFactory;

    /** @var array */
    protected $oroPackages = [];

    /** @var array */
    protected $thirdPartyPackages = [];

    /**
     * @param LocalRepositoryFactory $localRepositoryFactory
     */
    public function __construct(LocalRepositoryFactory $localRepositoryFactory)
    {
        $this->localRepositoryFactory = $localRepositoryFactory;
    }

    /**
     * @param bool $additional
     * @return PackageInterface[]
     */
    public function getOroPackages($additional = true)
    {
        $this->initPackages();

        if (false === $additional) {
            return array_intersect_key(
                $this->oroPackages,
                array_flip(
                    [
                        self::ORO_NAMESPACE . self::NAMESPACE_DELIMITER . self::PACKAGE_PLATFORM,
                        self::ORO_NAMESPACE . self::NAMESPACE_DELIMITER . self::PACKAGE_CRM,
                        self::ORO_NAMESPACE . self::NAMESPACE_DELIMITER . self::PACKAGE_COMMERCE,
                    ]
                )
            );
        }

        return $this->oroPackages;
    }

    /**
     * @return PackageInterface[]
     */
    public function getThirdPartyPackages()
    {
        $this->initPackages();

        return $this->thirdPartyPackages;
    }

    protected function initPackages()
    {
        $packages = $this->localRepositoryFactory->getLocalRepository()->getCanonicalPackages();

        foreach ($packages as $package) {
            if ($this->isOroPackage($package)) {
                $this->oroPackages[$package->getName()] = $package;
            } else {
                $this->thirdPartyPackages[$package->getName()] = $package;
            }
        }
    }

    /**
     * @param PackageInterface $package
     * @return bool
     */
    protected function isOroPackage(PackageInterface $package)
    {
        return 0 === strpos($package->getName(), self::ORO_NAMESPACE . self::NAMESPACE_DELIMITER);
    }
}
