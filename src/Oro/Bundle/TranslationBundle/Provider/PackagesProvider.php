<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Composer\Config;
use Composer\Package\PackageInterface;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;

class PackagesProvider implements PackageProviderInterface
{
    /** @var PackageManager */
    protected $pm;

    /** @var array */
    protected $bundles;

    /** @var  string */
    protected $kernelProjectDir;

    /** @var  string */
    protected $composerCacheHome;

    /** @var array|TranslationPackagesProviderExtensionInterface[] */
    protected $extensions = [];

    /** @var PackageProviderInterface[] */
    protected $packageProviders;

    /**
     * @param PackageManager $pm
     * @param array          $bundles
     * @param string         $kernelProjectDir
     * @param string         $composerCacheHome
     * @param array          $packageProviders
     */
    public function __construct(
        PackageManager $pm,
        array $bundles,
        $kernelProjectDir,
        $composerCacheHome,
        array $packageProviders = []
    ) {
        $this->pm = $pm;
        $this->bundles = $bundles;
        $this->kernelProjectDir = $kernelProjectDir;
        $this->composerCacheHome = $composerCacheHome;
        $this->packageProviders = $packageProviders;
    }

    /**
     * Set up specific environment for package manager
     *
     * @return PackageManager
     */
    protected function getPackageManager()
    {
        // avoid exception in Composer\Factory for creation service oro_distribution.composer
        if (!getenv('COMPOSER_HOME') && !getenv('HOME')) {
            putenv(sprintf('COMPOSER_HOME=%s', $this->composerCacheHome));

            // avoid change of current directory, just give correct vendor dir
            $rootPath                            = realpath($this->kernelProjectDir . '/') . DIRECTORY_SEPARATOR;
            Config::$defaultConfig['vendor-dir'] = $rootPath . Config::$defaultConfig['vendor-dir'];
        }

        return $this->pm;
    }

    /**
     * Collect installed packages through PackageManger
     * and add bundle namespaces to them
     *
     * @return array
     */
    public function getInstalledPackages()
    {
        $packages = $this->getPackageManager()->getInstalled();
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

        // collect extra package names from different extensions
        foreach ($this->packageProviders as $provider) {
            $packages = array_merge($packages, $provider->getInstalledPackages());
        }

        return array_unique($packages);
    }
}
