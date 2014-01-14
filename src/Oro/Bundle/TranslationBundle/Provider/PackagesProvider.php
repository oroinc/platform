<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Composer\Package\PackageInterface;

use Oro\Bundle\DistributionBundle\Manager\PackageManager;

class PackageManagerProvider
{
    /** @var PackageManager */
    protected $pm;

    /** @var array */
    protected $bundles;

    public function __construct(PackageManager $pm, array $bundles)
    {
        $this->pm      = $pm;
        $this->bundles = $bundles;
    }

    /**
     * Collect installed packages through PackageManger
     * and add bundle namespaces to them
     *
     * @return array
     */
    public function getInstalledPackages()
    {
        $packages = $this->pm->getInstalled();
        $packages = array_map(
            function (PackageInterface $package) {
                return $package->getName();
            },
            $packages
        );

        // collect bundle namespaces
        foreach ($this->bundles as $bundle) {
            $namespaceParts = explode('\\', $bundle);
            $packages[]     = reset($namespaceParts);
        }

        return array_unique($packages);
    }
}
