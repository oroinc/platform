<?php
namespace Oro\Bundle\DistributionBundle\Manager;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\ComposerRepository;
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
        return $this->composer->getRepositoryManager()->getLocalRepository()->getCanonicalPackages();
    }

    /**
     * @return array
     */
    public function getAvailable()
    {
        $packages = [];
        /** @var RepositoryInterface $repos */
        $repos = $this->composer->getRepositoryManager()->getRepositories();

        foreach($repos as $repo) {
            if ($repo instanceof ComposerRepository) {
                $packages = array_merge($packages, $repo->getProviderNames());
            } else {
                $packages = array_merge($packages, $repo->getPackages());
            }
        }

        return array_diff($packages, $this->getFlatListInstalledPackage());
    }



    /**
     * @return array
     */
    protected function getFlatListInstalledPackage()
    {
        $packages = [];
        foreach($this->getInstalled() as $package) {
            $packages[] = $package->getPrettyName();
        }

        return $packages;
    }
}