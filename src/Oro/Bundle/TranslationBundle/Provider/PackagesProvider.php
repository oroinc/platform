<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Composer\Config;
use Composer\Package\PackageInterface;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;

/**
 * Provider that returns the list of installed packages collected from the bundles and inner package providers.
 */
class PackagesProvider implements PackageProviderInterface
{
    /** @var array */
    protected $bundles;

    /** @var  string */
    protected $kernelProjectDir;

    /** @var array|TranslationPackagesProviderExtensionInterface[] */
    protected $extensions = [];

    /** @var PackageProviderInterface[] */
    protected $packageProviders;

    /**
     * @param array          $bundles
     * @param string         $kernelProjectDir
     * @param array          $packageProviders
     */
    public function __construct(
        array $bundles,
        $kernelProjectDir,
        array $packageProviders = []
    ) {
        $this->bundles = $bundles;
        $this->kernelProjectDir = $kernelProjectDir;
        $this->packageProviders = $packageProviders;
    }

    /**
     * Collect installed packages through PackageManger
     * and add bundle namespaces to them
     *
     * @return array
     */
    public function getInstalledPackages()
    {
        $packages = [];

        // collect bundle namespaces
        foreach ($this->bundles as $bundle) {
            $namespaceParts = explode('\\', $bundle);
            $packages[]     = reset($namespaceParts);
        }

        // collect extra package names from different extensions
        foreach ($this->packageProviders as $provider) {
            $packages = array_merge($packages, $provider->getInstalledPackages());
        }

        return array_unique($packages);
    }
}
