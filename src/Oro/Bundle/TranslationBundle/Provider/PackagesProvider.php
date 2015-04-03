<?php

namespace Oro\Bundle\TranslationBundle\Provider;

use Composer\Config;
use Composer\Package\PackageInterface;

use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

class PackagesProvider
{
    /** @var ServiceLink */
    protected $pmLink;

    /** @var array */
    protected $bundles;

    /** @var  string */
    protected $kernelRootDir;

    /** @var  string */
    protected $composerCacheHome;

    /**
     * @param ServiceLink $pmLink
     * @param array $bundles
     * @param string $kernelRootDir
     * @param string $composerCacheHome
     */
    public function __construct(ServiceLink $pmLink, array $bundles, $kernelRootDir, $composerCacheHome)
    {
        $this->pmLink            = $pmLink;
        $this->bundles           = $bundles;
        $this->kernelRootDir     = $kernelRootDir;
        $this->composerCacheHome = $composerCacheHome;
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
            $rootPath                            = realpath($this->kernelRootDir . '/../') . DIRECTORY_SEPARATOR;
            Config::$defaultConfig['vendor-dir'] = $rootPath . Config::$defaultConfig['vendor-dir'];
        }

        return $this->pmLink->getService();
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

        return array_unique($packages);
    }
}
