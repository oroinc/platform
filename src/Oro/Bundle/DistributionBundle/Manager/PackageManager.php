<?php
namespace Oro\Bundle\DistributionBundle\Manager;

use Composer\Composer;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\DependencyResolver\Pool;
use Composer\Installer;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Repository\ComposerRepository;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryInterface;
use Oro\Bundle\DistributionBundle\Exception\VerboseException;
use Oro\Bundle\DistributionBundle\Script\Runner;


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
     * @var Runner
     */
    protected $scriptRunner;

    /**
     * @var IOInterface
     */
    protected $composerIO;

    /**
     * @var string
     */
    protected $pathToComposerJson;

    /**
     * @param Composer $composer
     * @param Installer $installer
     * @param IOInterface $composerIO
     * @param Runner $scriptRunner
     * @param string $pathToComposerJson
     */
    public function __construct(Composer $composer, Installer $installer, IOInterface $composerIO, Runner $scriptRunner, $pathToComposerJson = null)
    {
        $this->composer = $composer;
        $this->installer = $installer;
        $this->composerIO = $composerIO;
        $this->scriptRunner = $scriptRunner;
        $this->pathToComposerJson = $pathToComposerJson;
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
     *
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
     * @param string $packageName
     * @param string $packageVersion
     *
     * @return string[]
     */
    public function getRequirements($packageName, $packageVersion = null)
    {
        $package = $this->getPreferredPackage($packageName, $packageVersion);

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
     *
     * @return bool
     */
    public function isPackageInstalled($packageName)
    {
        return (bool)$this->findPackage($packageName);
    }

    /**
     * @param string $packageName
     * @param string $packageVersion
     *
     * @throws \Oro\Bundle\DistributionBundle\Exception\VerboseException
     */
    public function install($packageName, $packageVersion = null)
    {
        $previousInstalled = $this->getFlatListInstalledPackage();
        $package = $this->getPreferredPackage($packageName, $packageVersion);
        $this->addToComposerJsonFile($package);
        if ($this->doInstall($package)) {
            foreach ($this->getInstalled() as $installedPackage) {
                if (!in_array($installedPackage->getName(), $previousInstalled)) {
                    $this->scriptRunner->install($installedPackage);
                }
            }
        } else {
            throw new VerboseException(sprintf('%s can\'t be installed!', $packageName), $this->composerIO->getOutput());
        }
    }

    /**
     * @param PackageInterface $package
     *
     * @return bool
     */
    public function doInstall(PackageInterface $package)
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
     * @param string $needleName
     *
     * @return array
     */
    public function getDependents($needleName)
    {
        $localRepository = $this->getLocalRepository();
        $dependents = [];
        /** @var PackageInterface $localPackage */
        foreach ($localRepository->getCanonicalPackages() as $localPackage) {
            $packageRequirements = array_reduce(
                array_merge($localPackage->getRequires(), $localPackage->getDevRequires()),
                function (array $result, Link $item) {
                    $result[] = $item->getTarget();
                    return $result;
                },
                []
            );

            if (in_array($needleName, $packageRequirements)) {
                $dependents[] = $localPackage->getName();
                $dependents = array_merge($dependents, $this->getDependents($localPackage->getName()));

            }
        }

        return $dependents;
    }

    /**
     * @param array $packageNames
     */
    public function uninstall(array $packageNames)
    {
        $this->removeFromComposerJson($packageNames);
        $installationManager = $this->composer->getInstallationManager();
        $localRepository = $this->getLocalRepository();
        foreach($packageNames as $name){
            $package = $this->findPackage($name);
            $this->scriptRunner->uninstall($package);
            $installationManager->uninstall(
                $localRepository,
                new UninstallOperation($package)
            );
        }
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

    /**
     * @param $packageName
     *
     * @return PackageInterface|bool
     */
    protected function findPackage($packageName)
    {
        $found = $this->getLocalRepository()->findPackages($packageName);
        if (!$found) {
            return false;
        }
        return $found[0];
    }

    /**
     * @param PackageInterface $package
     */
    protected function addToComposerJsonFile(PackageInterface $package)
    {
        $composerFile = new JsonFile($this->pathToComposerJson);
        $composerData = $composerFile->read();
        $composerData['require'][$package->getName()] = $package->getPrettyVersion();

        $composerFile->write($composerData);
        $this->updateRootPackage($composerData['require']);
    }

    /**
     * @param array $packageNames
     */
    protected function removeFromComposerJson(array $packageNames)
    {
        $composerFile = new JsonFile($this->pathToComposerJson);
        $composerData = $composerFile->read();
        foreach($packageNames as $name){
            unset($composerData['require'][$name]);
        }

        $composerFile->write($composerData);
    }
}
