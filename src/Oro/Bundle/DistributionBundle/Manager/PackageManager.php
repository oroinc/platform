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
use Oro\Bundle\DistributionBundle\Entity\PackageUpdate;
use Oro\Bundle\DistributionBundle\Exception\VerboseException;
use Oro\Bundle\DistributionBundle\Manager\Helper\ChangeSetBuilder;
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
     * @var array
     */
    protected $constantPackages = ['oro/platform', 'oro/platform-dist'];

    /**
     * @var Pool
     */
    protected $pool;


    /**
     * @param Composer $composer
     * @param Installer $installer
     * @param IOInterface $composerIO
     * @param Runner $scriptRunner
     * @param string $pathToComposerJson
     */
    public function __construct(
        Composer $composer,
        Installer $installer,
        IOInterface $composerIO,
        Runner $scriptRunner,
        $pathToComposerJson = null
    ) {
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
        $packages = [];

        $notificationUrl = new \ReflectionProperty('Composer\Package\Package', 'notificationUrl');
        $notificationUrl->setAccessible(true);

        $packages = array_filter(
            $this->getLocalRepository()->getCanonicalPackages(),
            function (PackageInterface $package) use ($notificationUrl) {
                return 'https://packagist.org/downloads/' != $notificationUrl->getValue($package);
            }
        );
        return $packages;
    }

    /**
     * @return PackageInterface[]
     */
    public function getAvailable()
    {
        $packageNames = [];
        $repositories = $this->getRepositories();

        $url = new \ReflectionProperty('Composer\Repository\ComposerRepository', 'url');
        $url->setAccessible(true);

        foreach ($repositories as $repo) {
            if ($repo instanceof ComposerRepository && $repo->hasProviders()) {
                // Dirty hack to filter the packages from packagist.org
                if ('http://packagist.org' == $url->getValue($repo)) {
                    continue;
                }
                $packageNames = array_merge($packageNames, $repo->getProviderNames());
            } else {
                /** @var PackageInterface[] $repoPackages */
                $repoPackages = $repo->getPackages();
                foreach ($repoPackages as $package) {
                    $packageNames[] = $package->getPrettyName();
                }
            }
        }

        $packages = array_reduce(
            array_unique($packageNames),
            function ($packages, $packageName) {
                $packages[] = $this->getPreferredPackage($packageName);
                return $packages;
            },
            []
        );

        return array_filter(
            $packages,
            function (PackageInterface $package) {
                return !$this->isPackageInstalled($package->getPrettyName());
            }
        );
    }

    /**
     * @param string $packageName
     * @return array
     */
    public function getAvailableVersions($packageName)
    {
        $versions = array_reduce(
            $this->createPool()->whatProvides($packageName),
            function (array $versions, PackageInterface $package) {
                if (!in_array($package->getPrettyVersion(), $versions)) {
                    $versions[] = $package->getPrettyVersion();
                }

                return $versions;
            },
            []
        );

        return $versions;
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
        $pool = $this->createPool();

        $constraint = null;
        if ($version) {
            $constraint = (new VersionParser())->parseConstraints($version);
        }
        /** @var PackageInterface[] $packages */
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
            $preferredPackageID = (new DefaultPolicy(
                $this->composer->getPackage()->getPreferStable()
            ))->selectPreferedPackages($pool, [], $packageIDs)[0];

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

        /** @var Link[] $requireLinks */
        $requireLinks = $package->getRequires();
        $nonPlatformLinks = array_filter(
            $requireLinks,
            function (Link $link) {
                return !preg_match(PlatformRepository::PLATFORM_PACKAGE_REGEX, $link->getTarget());
            }
        );

        return array_reduce(
            $nonPlatformLinks,
            function (array $requirements, Link $link) {
                $requirements[] = $link->getTarget();
                return $requirements;
            },
            []
        );
    }

    /**
     * @param string $packageName
     *
     * @return bool
     */
    public function isPackageInstalled($packageName)
    {
        return (bool)$this->findInstalledPackage($packageName);
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
        $this->updateComposerJsonFile($package, $packageVersion);

        try {
            if ($this->doInstall($package->getName())) {
                $installedPackages = $this->getInstalled();
                $justInstalledPackages = array_filter(
                    $installedPackages,
                    function (PackageInterface $package) use ($previousInstalled) {
                        return !in_array($package->getName(), $previousInstalled);
                    }
                );
                array_map(
                    function (PackageInterface $package) {
                        $this->scriptRunner->install($package);
                    },
                    $justInstalledPackages
                );
            } else {
                throw new VerboseException(
                    sprintf('%s can\'t be installed!', $packageName),
                    $this->composerIO->getOutput()
                );
            }
        } catch (\Exception $e) {
            $this->removeFromComposerJson([$packageName]);
            throw $e;
        }
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
     *
     * @throws \RuntimeException
     */
    public function uninstall(array $packageNames)
    {
        array_map(
            function ($name) {
                if (!$this->canBeDeleted($name)) {
                    throw new \RuntimeException(sprintf('Package %s is not deletable', $name));
                }
            },
            $packageNames
        );

        $this->removeFromComposerJson($packageNames);
        $installationManager = $this->composer->getInstallationManager();
        $localRepository = $this->getLocalRepository();

        $this->composer->getEventDispatcher()->dispatchCommandEvent('cache-clear', false);

        array_map(
            function ($name) use ($installationManager, $localRepository) {
                $package = $this->findInstalledPackage($name);
                $this->scriptRunner->uninstall($package);
                $installationManager->uninstall(
                    $localRepository,
                    new UninstallOperation($package)
                );
            },
            $packageNames
        );

    }

    /**
     * @return PackageUpdate[]
     */
    public function getAvailableUpdates()
    {
        $updates = array_reduce(
            $this->getInstalled(),
            function (array $result, PackageInterface $p) {
                $result[] = $this->getPackageUpdate($p);
                return $result;
            },
            []
        );

        return array_filter($updates);
    }

    /**
     * @param string $packageName
     *
     * @return bool
     */
    public function isUpdateAvailable($packageName)
    {
        $package = $this->findInstalledPackage($packageName);

        return (bool)$this->getPackageUpdate($package);
    }

    /**
     * @param PackageInterface $package
     *
     * @return null|PackageUpdate
     */
    public function getPackageUpdate(PackageInterface $package)
    {
        $preferredPackage = $this->getPreferredPackage(
            $package->getName(),
            null
        );
        if ($package->getSourceReference() !== $preferredPackage->getSourceReference()) {
            $versionString = '%s (%s)';

            return new PackageUpdate(
                $package->getName(),
                sprintf(
                    $versionString,
                    $package->getPrettyVersion(),
                    substr($package->getSourceReference(), 0, 7)
                ),
                sprintf(
                    $versionString,
                    $preferredPackage->getPrettyVersion(),
                    substr($preferredPackage->getSourceReference(), 0, 7)
                )
            );
        }

        return null;
    }

    /**
     * @param string $packageName
     * @throws VerboseException
     */
    public function update($packageName)
    {
        $previousInstalled = $this->getInstalled();
        $currentPackage = $this->findInstalledPackage($packageName);
        $this->updateComposerJsonFile($currentPackage, '*');
        if ($this->doInstall($packageName)) {
            $currentlyInstalled = $this->getInstalled();
            $changeSetBuilder = new ChangeSetBuilder();

            list($installedPackages, $updatedPackages, $uninstalledPackages) = $changeSetBuilder->build(
                $previousInstalled,
                $currentlyInstalled
            );
            array_map(
                function (PackageInterface $p) {
                    $this->scriptRunner->install($p);
                },
                $installedPackages
            );

            $fetchPreviousInstalledPackageVersion = function ($packageName) use ($previousInstalled) {
                foreach ($previousInstalled as $p) {
                    if ($p->getName() == $packageName) {

                        return $p->getVersion();
                    }
                }

                return '';
            };
            array_map(
                function (PackageInterface $p) use ($fetchPreviousInstalledPackageVersion) {
                    $previousInstalledPackageVersion = $fetchPreviousInstalledPackageVersion($p->getName());
                    $this->scriptRunner->update($p, $previousInstalledPackageVersion);
                },
                $updatedPackages
            );
            array_map(
                function (PackageInterface $p) {
                    $this->scriptRunner->uninstall($p);
                },
                $uninstalledPackages
            );
            $justInstalledPackage = $this->findInstalledPackage($packageName);
            $this->updateComposerJsonFile($justInstalledPackage, $justInstalledPackage->getPrettyVersion());
        } else {
            $this->updateComposerJsonFile($currentPackage, $currentPackage->getPrettyVersion());
            throw new VerboseException(
                sprintf('%s can\'t be updated!', $packageName),
                $this->composerIO->getOutput()
            );
        }
    }

    /**
     * @param string $packageName
     *
     * @return bool
     */
    public function canBeDeleted($packageName)
    {
        return !in_array($packageName, $this->constantPackages);
    }

    /**
     * @param string $packageName
     *
     * @return bool
     */
    protected function doInstall($packageName)
    {
        $this->installer
            ->setDryRun(false)
            ->setVerbose(false)
            ->setPreferSource(false)
            ->setPreferDist(true)
            ->setDevMode(false)
            ->setRunScripts(true)
            ->setUpdate(true)
            ->setUpdateWhitelist([$packageName])
            ->setOptimizeAutoloader(true);

        $result = $this->installer->run();

        return $result === 0;
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
        return array_reduce(
            $this->getInstalled(),
            function (array $packages, PackageInterface $package) {
                $packages[] = $package->getPrettyName();
                return $packages;
            },
            []
        );
    }

    /**
     * @param $packageName
     *
     * @return PackageInterface|bool
     */
    protected function findInstalledPackage($packageName)
    {
        $found = $this->getLocalRepository()->findPackages($packageName);
        if (!$found) {
            return false;
        }
        return $found[0];
    }

    /**
     * @param PackageInterface $package
     * @param string|null $version
     */
    protected function updateComposerJsonFile(PackageInterface $package, $version = null)
    {
        $composerFile = new JsonFile($this->pathToComposerJson);
        $composerData = $composerFile->read();
        $composerData['require'][$package->getName()] = $version ? : $package->getPrettyVersion();

        $composerFile->write($composerData);
        $this->updateRootPackage($composerData['require']);
    }

    /**
     * @param string[] $packageNames
     */
    protected function removeFromComposerJson(array $packageNames)
    {
        $composerFile = new JsonFile($this->pathToComposerJson);
        $composerData = $composerFile->read();
        foreach ($packageNames as $name) {
            unset($composerData['require'][$name]);
        }

        $composerFile->write($composerData);
    }

    /**
     * @return Pool
     */
    protected function createPool()
    {
        if ($this->pool) {
            return $this->pool;
        }

        $pool = new Pool('dev');
        $pool->addRepository(new CompositeRepository($this->getRepositories()));

        return $this->pool = $pool;
    }
}
