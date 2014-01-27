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

    public function __construct(ServiceLink $pmLink, array $bundles, $kernelRootDir)
    {
        $this->pmLink        = $pmLink;
        $this->bundles       = $bundles;
        $this->kernelRootDir = $kernelRootDir;
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
            putenv(sprintf('COMPOSER_HOME=%s/cache/composer', $this->kernelRootDir));

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
