<?php
namespace Oro\Bundle\DistributionBundle\Manager;

use Composer\Composer;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Pool;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Repository\ComposerRepository;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;

class PackageManager
{
    /**
     * @var Composer
     */
    protected $composer;

    /**
     * @param Composer $composer
     */
    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
    }

    /**
     * @return PackageInterface[]
     */
    public function getInstalled()
    {
        return $this->getLocalRepository()->getCanonicalPackages();
    }

    /**
     * @return array
     */
    public function getAvailable()
    {
        $packages = [];
        $repositories = $this->getRepositories();

        foreach ($repositories as $repo) {
            if ($repo instanceof ComposerRepository && $repo->hasProviders()) {
                $packages = array_merge($packages, $repo->getProviderNames());
            } else {
                /** @var PackageInterface $package */
                foreach ($repo->getPackages() as $package) {
                    $packages[] = $package->getPrettyName();
                }
            }
        }

        return array_values(array_unique(array_diff($packages, $this->getFlatListInstalledPackage())));
    }


    /**
     * @return array
     */
    protected function getFlatListInstalledPackage()
    {
        $packages = [];
        foreach ($this->getInstalled() as $package) {
            $packages[] = $package->getPrettyName();
        }

        return $packages;
    }

    /**
     */
    public function install($packageName, $version = null)
    {

    }

    /**
     * @param $packageName
     * @param $version
     * @return PackageInterface
     * @throws \RuntimeException
     */
    public function getPreferredPackage($packageName, $version = null)
    {
        $pool = new Pool();
        $pool->addRepository(new CompositeRepository($this->getRepositories()));

        $constraint = null;
        if ($version) {
            $constraint = (new VersionParser())->parseConstraints($version);
        }
        $packages = $pool->whatProvides($packageName, $constraint);
        $totalPackages = count($packages);
        if (!$totalPackages) {
            throw new \RuntimeException(sprintf('Cannot find package %s %s', $packageName, $version));
        }
        if ($totalPackages == 1) {
            $package = $packages[0];
        } else {
            $packageIDs = [];
            foreach ($packages as $index => $package) {
                // skip providers/replacers
                if ($package->getName() !== $packageName) {
                    unset($packages[$index]);
                    continue;
                }
                $packageIDs[$index] = $package->getId();
            }
            $preferredPackageID = (new DefaultPolicy())->selectPreferedPackages($pool, [], $packageIDs)[0];
            $package = $pool->literalToPackage($preferredPackageID);
        }

        return $package;
    }

    public function getRequirements(PackageInterface $package)
    {
        $requirements = [];
        /** @var Link[] $requirementLinks */
        $requirementLinks = $package->getRequires();
        foreach ($requirementLinks as $link) {
            if (!preg_match(PlatformRepository::PLATFORM_PACKAGE_REGEX, $link->getTarget())) {
                $requirements[] = $link->getTarget();
            }
        }

        return $requirements;
    }

    /**
     * @param $packageName
     * @return bool
     */
    public function isPackageInstalled($packageName)
    {
        return (bool)$this->getLocalRepository()->findPackages($packageName);
    }

    /**
     * @return \Composer\Repository\WritableRepositoryInterface
     */
    protected function getLocalRepository()
    {
        return $this->composer->getRepositoryManager()->getLocalRepository();
    }

    /**
     * @return RepositoryInterface[]
     */
    protected function getRepositories()
    {
        return $this->composer->getRepositoryManager()->getRepositories();
    }
}
