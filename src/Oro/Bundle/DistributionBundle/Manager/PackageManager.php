<?php
namespace Oro\Bundle\DistributionBundle\Manager;

use Composer\Composer;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Pool;
use Composer\Installer;
use Composer\Json\JsonFile;
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
     * @var Installer
     */
    protected $installer;

    /**
     * @param Composer $composer
     * @param Installer $installer
     */
    public function __construct(Composer $composer, Installer $installer)
    {
        $this->composer = $composer;
        $this->installer = $installer;
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
     * @param string $packageName
     * @param mixed $version
     * @return PackageInterface
     * @throws \RuntimeException
     */
    public function getPreferredPackage($packageName, $version = null)
    {
        $pool = new Pool('dev');
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

    /**
     * @param PackageInterface $package
     * @return string[]
     */
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
     * @param string $packageName
     * @return bool
     */
    public function isPackageInstalled($packageName)
    {
        return (bool)$this->getLocalRepository()->findPackages($packageName);
    }

    /**
     * @param PackageInterface $package
     * @param string $configPath
     */
    public function addToComposerJsonFile(PackageInterface $package, $configPath = './composer.json')
    {
        $composerFile = new JsonFile($configPath);
        $composerData = $composerFile->read();
        $composerData['require'][$package->getName()] = $package->getPrettyVersion();

        $composerFile->write($composerData);
        $this->updateRootPackage($composerData['require']);
    }

    /**
     * @param PackageInterface $package
     * @return bool
     */
    public function install(PackageInterface $package)
    {
        $this->installer
            ->setDryRun(false)
            ->setVerbose(false)
            ->setPreferSource(false)
            ->setPreferDist(true)
            ->setDevMode(false)
            ->setRunScripts(true)
            ->setUpdate(true)
            ->setUpdateWhitelist([$package->getName()])
            ->setOptimizeAutoloader(true);

        return $this->installer->run();
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

    /**
     * @param array $require
     */
    protected function updateRootPackage(array $require = [])
    {
        $rootPackage = $this->composer->getPackage();
        $rootPackage->setRequires(
            (new VersionParser())->parseLinks(
                $rootPackage->getName(),
                $rootPackage->getPrettyVersion(),
                'requires',
                $require
            )
        );
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
}
