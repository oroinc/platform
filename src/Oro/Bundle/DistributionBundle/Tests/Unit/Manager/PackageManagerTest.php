<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Manager;

use Composer\Composer;
use Composer\Installer;
use Composer\IO\BufferIO;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Repository\ArrayRepository;
use Composer\Repository\ComposerRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableArrayRepository;
use Composer\Repository\WritableRepositoryInterface;
use Composer\Installer\InstallationManager;
use Oro\Bundle\DistributionBundle\Entity\PackageRequirement;
use Oro\Bundle\DistributionBundle\Entity\PackageUpdate;
use Oro\Bundle\DistributionBundle\Exception\VerboseException;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\MockHelperTrait;
use Oro\Bundle\DistributionBundle\Script\Runner;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\ReflectionHelperTrait;
use Oro\Bundle\PlatformBundle\Maintenance\Mode as MaintenanceMode;
use Oro\Bundle\PlatformBundle\OroPlatformBundle;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PackageManagerTest extends \PHPUnit_Framework_TestCase
{
    use MockHelperTrait;
    use ReflectionHelperTrait;

    /**
     * @test
     */
    public function shouldBeConstructedWithComposerAndInstallerAndIOAndScriptRunner()
    {
        $this->createPackageManager();
    }

    /**
     * @test
     */
    public function shouldReturnInstalledPackages()
    {
        $composer = $this->createComposerMock();
        $repositoryManagerMock = $this->createRepositoryManagerMock();

        $composer->expects($this->once())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManagerMock));

        $localRepository = new WritableArrayRepository(
            $installedPackages = [$this->getPackage('my/package', 1)]
        );
        $repositoryManagerMock->expects($this->once())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));

        $manager = $this->createPackageManager($composer);
        $this->assertEquals($installedPackages, $manager->getInstalled());
    }

    /**
     * @test
     */
    public function shouldReturnAvailablePackages()
    {
        $composer = $this->createComposerMock();
        $repositoryManagerMock = $this->createRepositoryManagerMock();

        // Local repo with installed packages
        $installedPackages = [$this->getPackage('name1', 1), $this->getPackage('name5', 1)];
        $localRepository = new WritableArrayRepository($installedPackages);

        // Remote repos
        $duplicatedPackageName = uniqid();
        $packagistRepositoryMock = $this->createComposerRepositoryMock();
        $this->writeAttribute($packagistRepositoryMock, 'url', 'http://packagist.org');
        $composerRepositoryMock = $this->createComposerRepositoryMock();
        $composerRepositoryWithoutProvidersMock = $this->createComposerRepositoryMock();
        $anyRepositoryExceptComposerRepository = new ArrayRepository(
            [$this->getPackage('name4', 1), $this->getPackage($duplicatedPackageName, 1)]
        );

        // Get remote repos
        $composer->expects($this->any())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManagerMock));

        $repositoryManagerMock->expects($this->any())
            ->method('getRepositories')
            ->will(
                $this->returnValue(
                    [
                        $composerRepositoryMock,
                        $composerRepositoryWithoutProvidersMock,
                        $packagistRepositoryMock,
                        $anyRepositoryExceptComposerRepository
                    ]
                )
            );

        // Get local repo
        $repositoryManagerMock->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));

        // Fetch available packages configuration
        // from composer repo
        $availablePackage1 = $this->getPackage('name1', 1);
        $availablePackage1->setRepository($composerRepositoryMock);
        $availablePackage2 = $this->getPackage($duplicatedPackageName, 1);
        $availablePackage2->setRepository($composerRepositoryMock);
        $availablePackage3 = $this->getPackage('name2', 1);
        $availablePackage3->setRepository($composerRepositoryMock);
        $composerRepositoryMock->expects($this->any())
            ->method('hasProviders')
            ->will($this->returnValue(true));

        $composerRepositoryMock->expects($this->once())
            ->method('getProviderNames')
            ->will($this->returnValue(['name1', 'name2']));

        $composerRepositoryMock->expects($this->any())
            ->method('whatProvides')
            ->will($this->returnValue([$availablePackage1, $availablePackage3]));

        // packagist.org repository
        $packagistRepositoryMock->expects($this->any())
            ->method('hasProviders')
            ->will($this->returnValue(true));

        $packagistRepositoryMock->expects($this->never())
            ->method('getProviderNames');

        $packagistRepositoryMock->expects($this->any())
            ->method('whatProvides')
            ->will($this->returnValue([]));

        // from composer repo without providers
        $composerRepositoryWithoutProvidersMock->expects($this->any())
            ->method('hasProviders')
            ->will($this->returnValue(false));

        $composerRepositoryWithoutProvidersMock->expects($this->atLeastOnce())
            ->method('getPackages')
            ->will($this->returnValue([$availablePackage1, $availablePackage2]));

        // Ready Steady Go!
        $manager = $this->createPackageManager($composer);

        $packages = array_reduce(
            $manager->getAvailable(),
            function ($result, PackageInterface $package) {
                $result[] = $package->getPrettyName();
                return $result;
            },
            []
        );
        $this->assertEquals(['name2', $duplicatedPackageName, 'name4'], $packages);
    }

    /**
     * @test
     */
    public function shouldReturnPackageRequirementsWithoutPlatformRequirements()
    {
        $platformRequirement = 'php-64bit';
        $packageName = 'vendor/package';
        $packageVersion = '*';

        // guard. Platform requirement is the one that matches following regexp
        $this->assertRegExp(PlatformRepository::PLATFORM_PACKAGE_REGEX, $platformRequirement);

        $requirementLinkMock1 = $this->createComposerPackageLinkMock();
        $requirementLinkMock2 = $this->createComposerPackageLinkMock();
        $platformRequirementLinkMock = $this->createComposerPackageLinkMock();

        // non platform requirements
        $requirementLinkMock1->expects($this->exactly(2))
            ->method('getTarget')
            ->will($this->returnValue('requirement1'));

        $requirementLinkMock2->expects($this->exactly(2))
            ->method('getTarget')
            ->will($this->returnValue('requirement2'));

        // platform dependency
        $platformRequirementLinkMock->expects($this->once())
            ->method('getTarget')
            ->will($this->returnValue($platformRequirement));

        // package mock configuration
        $packageMock = $this->createPackageMock($packageName);
        $packageMock->expects($this->once())
            ->method('getRequires')
            ->will($this->returnValue([$requirementLinkMock1, $requirementLinkMock2, $platformRequirementLinkMock]));
        $packageMock->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue($packageVersion));
        $packageMock->expects($this->once())
            ->method('getNames')
            ->will($this->returnValue([$packageName]));
        $packageMock->expects($this->any())
            ->method('getStability')
            ->will($this->returnValue('stable'));

        // composer and repository config
        $composer = $this->createComposerMock();
        $repositoryManagerMock = $this->createRepositoryManagerMock();
        $composer->expects($this->exactly(2))
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManagerMock));

        $localRepository = new WritableArrayRepository([$packageMock, $this->getPackage('requirement2', 1)]);

        $repositoryManagerMock->expects($this->once())
            ->method('getRepositories')
            ->will($this->returnValue([$localRepository]));
        $repositoryManagerMock->expects($this->once())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));

        $manager = $this->createPackageManager($composer);
        $expectedRequirements = [
            new PackageRequirement('requirement1', false),
            new PackageRequirement('requirement2', true)
        ];

        $this->assertEquals($expectedRequirements, $manager->getRequirements($packageName, $packageVersion));
    }

    /**
     * @test
     */
    public function shouldReturnTrueForInstalledPackagesFalseOtherwise()
    {
        $notInstalledPackageName = 'not-installed/package';

        $composer = $this->createComposerMock();
        $repositoryManagerMock = $this->createRepositoryManagerMock();

        $installedPackage = $this->getPackage('installed/package', 1);
        $localRepository = new WritableArrayRepository([$installedPackage]);

        $composer->expects($this->exactly(2))
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManagerMock));

        $repositoryManagerMock->expects($this->exactly(2))
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));

        $manager = $this->createPackageManager($composer);

        $this->assertFalse($manager->isPackageInstalled($notInstalledPackageName));
        $this->assertTrue($manager->isPackageInstalled($installedPackage->getName()));
    }

    /**
     * @test
     */
    public function shouldRunInstallerAddPackageToComposerJsonAndUpdateRootPackage()
    {
        $newPackageName = 'new-vendor/new-package';
        $newPackageVersion = 'v3';
        $newPackage = $this->getPackage($newPackageName, $newPackageVersion);

        // temporary composer.json data
        $composerJsonData = [
            'require' => [
                'vendor1/package1' => 'v1',
                'vendor2/package2' => 'v2',
            ]
        ];

        $expectedJsonData = $composerJsonData;
        $expectedJsonData['require'][$newPackageName] = $newPackageVersion;

        $tempComposerJson = tempnam(sys_get_temp_dir(), 'composer.json');
        file_put_contents($tempComposerJson, json_encode($composerJsonData));

        // composer and repository
        $composer = $this->createComposerMock();
        $repositoryManager = $this->createRepositoryManagerMock();

        $localRepository = $this->createLocalRepositoryMock();

        $composer->expects($this->any())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));
        $repositoryManager->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));
        $repositoryManager->expects($this->once())
            ->method('getRepositories')
            ->will($this->returnValue([new WritableArrayRepository([$newPackage])]));
        $localRepository->expects($this->at(0))
            ->method('getCanonicalPackages')
            ->will($this->returnValue([$this->getPackage('name', 1)]));
        $localRepository->expects($this->at(1))
            ->method('getCanonicalPackages')
            ->will($this->returnValue([$this->getPackage('name', 1), $newPackage]));

        $localRepository->expects($this->any())
            ->method('findPackages')
            ->will($this->returnValue([$newPackage]));

        $runner = $this->createScriptRunnerMock();
        $runner->expects($this->once())
            ->method('clearDistApplicationCache');
        $runner->expects($this->once())
            ->method('clearApplicationCache');
        $runner->expects($this->once())
            ->method('runPlatformUpdate');
        $runner->expects($this->once())
            ->method('runInstallScripts')
            ->with($this->isInstanceOf('Composer\Package\PackageInterface'));

        /** @var \PHPUnit_Framework_MockObject_MockObject $rootPackageMock */
        $rootPackageMock = $composer->getPackage();
        $rootPackageMock->expects($this->once())
            ->method('setRequires');
        $rootPackageMock->expects($this->once())
            ->method('getName');
        $rootPackageMock->expects($this->once())
            ->method('getPrettyVersion');

        $composer->expects($this->once())
            ->method('getPackage')
            ->will($this->returnValue($rootPackageMock));

        $composerInstaller = $this->prepareInstallerMock($newPackage->getName(), 0);

        $maintenance = $this->getEnableMaintenanceMock();

        $logger = $this->createLoggerMock();
        $logger->expects($this->at(0))->method('info')->with($this->stringContains('installing begin'));
        $logger->expects($this->at(1))->method('info')->with($this->stringContains('Updating composer.json'));
        $logger->expects($this->at(2))->method('info')->with($this->stringContains('installed'));
        $logger->expects($this->never())->method('error');

        $manager = $this->createPackageManager(
            $composer,
            $composerInstaller,
            null,
            $runner,
            $maintenance,
            $logger,
            $tempComposerJson
        );
        $manager->install($newPackage->getName());

        $updatedComposerData = json_decode(file_get_contents($tempComposerJson), true);
        unlink($tempComposerJson);

        $this->assertEquals($expectedJsonData, $updatedComposerData);
    }

    /**
     * @test
     *
     * @param bool $loadDemoData
     */
    public function shouldLoadDemoData($loadDemoData = true)
    {
        $newPackageName = 'new-vendor/new-package';
        $newPackageVersion = 'v3';

        $composer = $this->createComposerMock();
        $repositoryManager = $this->createRepositoryManagerMock();

        $localRepository = new WritableArrayRepository([$this->getPackage($newPackageName, $newPackageVersion)]);

        $composer->expects($this->any())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));
        $repositoryManager->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));
        $repositoryManager->expects($this->once())
            ->method('getRepositories')
            ->will($this->returnValue([$localRepository]));

        $runner = $this->createScriptRunnerMock();
        $runner->expects($this->once())
            ->method('loadDemoData');

        $composerInstaller = $this->prepareInstallerMock($newPackageName, 0);
        $manager = $this->createPackageManager($composer, $composerInstaller, null, $runner);

        $manager->install($newPackageName, $newPackageVersion, $loadDemoData);
    }

    /**
     * @test
     *
     * @param bool $loadDemoData
     */
    public function shouldNotLoadDemoData($loadDemoData = false)
    {
        $newPackageName = 'new-vendor/new-package';
        $newPackageVersion = 'v3';

        $composer = $this->createComposerMock();
        $repositoryManager = $this->createRepositoryManagerMock();

        $localRepository = new WritableArrayRepository([$this->getPackage($newPackageName, $newPackageVersion)]);

        $composer->expects($this->any())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));
        $repositoryManager->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));
        $repositoryManager->expects($this->once())
            ->method('getRepositories')
            ->will($this->returnValue([$localRepository]));

        $runner = $this->createScriptRunnerMock();
        $runner->expects($this->never())
            ->method('loadDemoData');

        $composerInstaller = $this->prepareInstallerMock($newPackageName, 0);
        $manager = $this->createPackageManager($composer, $composerInstaller, null, $runner);

        $manager->install($newPackageName, $newPackageVersion, $loadDemoData);
    }

    /**
     * @test
     */
    public function throwVerboseExceptionWhenInstallationFailed()
    {
        $newPackageName = 'new-vendor/new-package';
        $newPackageVersion = 'v3';
        $newPackage = $this->getPackage($newPackageName, $newPackageVersion);

        // temporary composer.json data
        $composerJsonData = [
            'require' => [
                'vendor1/package1' => 'v1',
                'vendor2/package2' => 'v2',
            ]
        ];

        $tempComposerJson = tempnam(sys_get_temp_dir(), 'composer.json');
        file_put_contents($tempComposerJson, json_encode($composerJsonData));

        // composer and repository
        $composer = $this->createComposerMock();
        $repositoryManager = $this->createRepositoryManagerMock();
        $installedPackages = [$newPackage];
        $localRepository = new WritableArrayRepository($installedPackages);

        $composer->expects($this->any())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));
        $repositoryManager->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));
        $repositoryManager->expects($this->once())
            ->method('getRepositories')
            ->will($this->returnValue([$localRepository]));

        /** @var \PHPUnit_Framework_MockObject_MockObject $rootPackageMock */
        $rootPackageMock = $composer->getPackage();

        $composer->expects($this->once())
            ->method('getPackage')
            ->will($this->returnValue($rootPackageMock));

        $composerInstaller = $this->prepareInstallerMock($newPackage->getName(), 1);
        $bufferMock = $this->createBufferIoMock($bufferOutput = 'Some output');


        $logger = $this->createLoggerMock();
        $logger->expects($this->at(0))
            ->method('info')
            ->with($this->stringContains('installing begin'));
        $logger->expects($this->at(1))
            ->method('info')
            ->with($this->stringContains('Updating composer.json'));
        $logger->expects($this->at(2))
            ->method('error')
            ->with($this->stringContains('can\'t be installed'));
        $logger->expects($this->at(3))
            ->method('error')
            ->with($bufferOutput);
        $logger->expects($this->at(4))
            ->method('info')
            ->with($this->stringContains('Removing from composer.json'));

        $manager = $this->createPackageManager(
            $composer,
            $composerInstaller,
            $bufferMock,
            null,
            null,
            $logger,
            $tempComposerJson
        );

        try {
            $manager->install($newPackage->getName());
        } catch (VerboseException $e) {
            $composerDataAfterFail = json_decode(file_get_contents($tempComposerJson), true);
            unlink($tempComposerJson);

            $this->assertEquals("{$newPackageName} can't be installed!", $e->getMessage());
            $this->assertEquals($composerJsonData, $composerDataAfterFail);

            return;
        }

        unlink($tempComposerJson);
        $this->fail('Exception wasn\'t caught');
    }

    /**
     * @test
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function shouldRemoveNewPackageFromJsonAndRethrowRunnerExceptionDuringInstallation()
    {
        $newPackageName = 'new-vendor/new-package';
        $newPackageVersion = 'v3';
        $newPackage = $this->getPackage($newPackageName, $newPackageVersion);

        // temporary composer.json data
        $composerJsonData = [
            'require' => [
                'vendor1/package1' => 'v1',
                'vendor2/package2' => 'v2',
            ]
        ];

        $tempComposerJson = tempnam(sys_get_temp_dir(), 'composer.json');
        file_put_contents($tempComposerJson, json_encode($composerJsonData));

        // composer and repository
        $composer = $this->createComposerMock();
        $repositoryManager = $this->createRepositoryManagerMock();
        $localRepository = $this->getMock('Composer\Repository\WritableArrayRepository');

        $composer->expects($this->any())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));

        // Local repository
        $repositoryManager->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));
        // Fetch previous installed
        $localRepository->expects($this->at(0))
            ->method('getCanonicalPackages')
            ->will($this->returnValue([]));
        // Fetch packages after install
        $localRepository->expects($this->at(1))
            ->method('getCanonicalPackages')
            ->will($this->returnValue([$newPackage]));
        $localRepository->expects($this->any())
            ->method('findPackages')
            ->will($this->returnValue([$newPackage]));

        $installedPackages = [$newPackage];

        // Package repositories
        $repositoryManager->expects($this->once())
            ->method('getRepositories')
            ->will($this->returnValue([new WritableArrayRepository($installedPackages)]));

        /** @var \PHPUnit_Framework_MockObject_MockObject $rootPackageMock */
        $rootPackageMock = $composer->getPackage();

        $composer->expects($this->once())
            ->method('getPackage')
            ->will($this->returnValue($rootPackageMock));

        $scriptRunner = $this->createScriptRunnerMock();
        $scriptRunner->expects($this->any())
            ->method('runInstallScripts')
            ->will($this->throwException($thrownException = new \Exception('Exception message')));

        $composerInstaller = $this->prepareInstallerMock($newPackage->getName(), 0);

        $logger = $this->createLoggerMock();
        $logger->expects($this->at(0))
            ->method('info')
            ->with($this->stringContains('installing begin'));
        $logger->expects($this->at(1))
            ->method('info')
            ->with($this->stringContains('Updating composer.json'));
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('exception message'));
        $logger->expects($this->at(3))
            ->method('info')
            ->with($this->stringContains('Removing from composer.json'));
        /** Clean up after error during running install scripts */
        $logger->expects($this->at(4))
            ->method('info')
            ->with($this->stringContains('Removing just installed packages'), [$newPackageName]);
        // Cache clear
        $eventDispatcher = $this->createConstructorLessMock('Composer\EventDispatcher\EventDispatcher');
        $composer->expects($this->once())
            ->method('getEventDispatcher')
            ->will($this->returnValue($eventDispatcher));
        $eventDispatcher->expects($this->once())
            ->method('dispatchCommandEvent')
            ->with('cache-clear', false);
        $installationManager = $this->createInstallationManagerMock();
        $installationManager->expects($this->once())
            ->method('uninstall')
            ->with(
                $this->equalTo($localRepository),
                $this->isInstanceOf('Composer\DependencyResolver\Operation\UninstallOperation')
            );
        $composer->expects($this->any())
            ->method('getInstallationManager')
            ->will($this->returnValue($installationManager));
        /** End clean up */

        $manager = $this->createPackageManager(
            $composer,
            $composerInstaller,
            null,
            $scriptRunner,
            null,
            $logger,
            $tempComposerJson
        );

        try {
            $manager->install($newPackage->getName());
        } catch (\Exception $e) {
            $composerDataAfterFail = json_decode(file_get_contents($tempComposerJson), true);
            unlink($tempComposerJson);

            $this->assertSame($thrownException, $e);
            $this->assertEquals($composerJsonData, $composerDataAfterFail);

            return;
        }

        unlink($tempComposerJson);
        $this->fail('Exception wasn\'t caught');
    }

    /**
     * @test
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Cannot find package my/package
     */
    public function throwExceptionWhenCanNotFindPreferredPackage()
    {
        $composer = $this->createComposerMock();

        $repositoryManager = $this->createRepositoryManagerMock();
        $composer->expects($this->once())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));

        $repository = new ArrayRepository();
        $repositoryManager->expects($this->once())
            ->method('getRepositories')
            ->will($this->returnValue([$repository]));

        $manager = $this->createPackageManager($composer);

        $manager->getPreferredPackage('my/package');
    }

    /**
     * @test
     */
    public function shouldReturnPreferredPackageForOnePackage()
    {
        $composer = $this->createComposerMock();

        $repositoryManager = $this->createRepositoryManagerMock();
        $composer->expects($this->once())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));

        $repository = new ArrayRepository([$package = $this->getPackage('my/package', '1')]);
        $repositoryManager->expects($this->once())
            ->method('getRepositories')
            ->will($this->returnValue([$repository]));

        $manager = $this->createPackageManager($composer);

        $this->assertSame($package, $manager->getPreferredPackage($package->getName(), $package->getVersion()));
    }

    /**
     * @test
     */
    public function shouldReturnPreferredPackageForPackages()
    {
        $composer = $this->createComposerMock();

        $repositoryManager = $this->createRepositoryManagerMock();
        $composer->expects($this->once())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));

        $package1 = $this->getPackage('my/package', '1');
        $repository = new ArrayRepository(
            [
                $package1,
                $this->getPackage('my/package', '2'),
            ]
        );
        $repositoryManager->expects($this->once())
            ->method('getRepositories')
            ->will($this->returnValue([$repository]));

        $manager = $this->createPackageManager($composer);

        $this->assertSame($package1, $manager->getPreferredPackage($package1->getName(), $package1->getVersion()));
    }

    /**
     * @test
     */
    public function shouldReturnNewestPreferredPackage()
    {
        $composer = $this->createComposerMock();

        $repositoryManager = $this->createRepositoryManagerMock();
        $composer->expects($this->once())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));

        $packageName = 'my/package';
        $freshPackage = $this->getPackage($packageName, '2');
        $repository = new ArrayRepository(
            [
                $this->getPackage($packageName, '1'),
                $freshPackage,
            ]
        );
        $repositoryManager->expects($this->once())
            ->method('getRepositories')
            ->will($this->returnValue([$repository]));

        $manager = $this->createPackageManager($composer);

        $this->assertSame($freshPackage, $manager->getPreferredPackage($packageName));
    }

    /**
     * @test
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function shouldUninstallViaInstallationManagerAndUpdateComposerJson()
    {
        $packageNamesToBeRemoved = ['vendor2/package2', 'vendor3/package3'];
        $composerJsonData = [
            'require' => [
                'vendor1/package1' => 'v1',
                $packageNamesToBeRemoved[0] => 'v2',
                $packageNamesToBeRemoved[1] => 'v2',
                'vendor4/package4' => 'v3',
            ]
        ];
        $expectedJsonData = $composerJsonData;
        unset($expectedJsonData['require'][$packageNamesToBeRemoved[0]]);
        unset($expectedJsonData['require'][$packageNamesToBeRemoved[1]]);

        $tempComposerJson = tempnam(sys_get_temp_dir(), 'composer.json');
        file_put_contents($tempComposerJson, json_encode($composerJsonData));

        // composer and repository
        $composer = $this->createComposerMock();
        $repositoryManager = $this->createRepositoryManagerMock();
        $installationManager = $this->createInstallationManagerMock();
        $localRepository = new WritableArrayRepository(
            $installedPackages = [
                $this->getPackage($packageNamesToBeRemoved[0], 'v2'),
                $this->getPackage($packageNamesToBeRemoved[1], 'v2')
            ]
        );

        $composer->expects($this->any())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));
        $repositoryManager->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));
        $composer->expects($this->any())
            ->method('getInstallationManager')
            ->will($this->returnValue($installationManager));

        // Cache clear
        $eventDispatcher = $this->createConstructorLessMock('Composer\EventDispatcher\EventDispatcher');
        $composer->expects($this->once())
            ->method('getEventDispatcher')
            ->will($this->returnValue($eventDispatcher));
        $eventDispatcher->expects($this->once())
            ->method('dispatchCommandEvent')
            ->with('cache-clear', false);

        // Uninstalling
        $installationManager->expects($this->exactly(count($packageNamesToBeRemoved)))
            ->method('uninstall')
            ->with(
                $this->equalTo($localRepository),
                $this->isInstanceOf('Composer\DependencyResolver\Operation\UninstallOperation')
            );

        // run uninstall scripts
        $runner = $this->createScriptRunnerMock();
        $runner->expects($this->at(0))
            ->method('runUninstallScripts')
            ->with($installedPackages[0]);
        $runner->expects($this->at(1))
            ->method('runUninstallScripts')
            ->with($installedPackages[1]);
        $runner->expects($this->once())
            ->method('removeCachedFiles');
        $runner->expects($this->once())
            ->method('clearDistApplicationCache');

        $maintenance = $this->getEnableMaintenanceMock();

        $logger = $this->createLoggerMock();
        $logger->expects($this->at(0))
            ->method('info')
            ->with($this->stringContains('Uninstalling begin'));
        $logger->expects($this->at(1))
            ->method('info')
            ->with($this->stringContains('Removing from composer.json'));
        $logger->expects($this->at(2))
            ->method('info')
            ->with($this->stringContains('Packages uninstalled'));
        $logger->expects($this->never())
            ->method('error');

        // Ready Steady Go!
        $manager = $this->createPackageManager(
            $composer,
            null,
            null,
            $runner,
            $maintenance,
            $logger,
            $tempComposerJson
        );
        $manager->uninstall($packageNamesToBeRemoved);

        $updatedComposerData = json_decode(file_get_contents($tempComposerJson), true);
        unlink($tempComposerJson);

        $this->assertEquals($expectedJsonData, $updatedComposerData);
    }

    /**
     * @test
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Package oro/platform is not deletable
     */
    public function throwsExceptionWhenTryingToUninstallPlatform()
    {
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Uninstalling begin'));
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Package oro/platform is not deletable'));

        $manager = $this->createPackageManager(null, null, null, null, null, $logger);
        $manager->uninstall([OroPlatformBundle::PACKAGE_NAME]);
    }

    /**
     * @test
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Package oro/platform-dist is not deletable
     */
    public function throwsExceptionWhenTryingToUninstallPlatformDist()
    {
        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('info')
            ->with($this->stringContains('Uninstalling begin'));
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Package oro/platform-dist is not deletable'));

        $manager = $this->createPackageManager(null, null, null, null, null, $logger);
        $manager->uninstall([OroPlatformBundle::PACKAGE_DIST_NAME]);
    }

    /**
     * @test
     */
    public function shouldNotAllowToDeletePlatformAndPlatformDist()
    {
        $manager = $this->createPackageManager();

        $this->assertFalse($manager->canBeDeleted(OroPlatformBundle::PACKAGE_NAME));
        $this->assertFalse($manager->canBeDeleted(OroPlatformBundle::PACKAGE_DIST_NAME));
    }

    /**
     * @test
     */
    public function shouldReturnDependentsListRecursively()
    {
        $packageName = 'vendor/package';
        $expectedDependents = ['vendor1/package1', 'vendor2/package2', 'vendor3/package3'];
        $packageLink = $this->createComposerPackageLinkMock();
        $packageLink->expects($this->any())
            ->method('getTarget')
            ->will($this->returnValue($packageName));

        $packageLink1 = $this->createComposerPackageLinkMock();
        $packageLink1->expects($this->any())
            ->method('getTarget')
            ->will($this->returnValue($expectedDependents[0]));

        $package1 = $this->createPackageMock($expectedDependents[0]);
        $package1->expects($this->any())
            ->method('getRequires')
            ->will($this->returnValue([$packageLink]));
        $package1->expects($this->any())
            ->method('getDevRequires')
            ->will($this->returnValue([]));

        $package2 = $this->createPackageMock($expectedDependents[1]);
        $package2->expects($this->any())
            ->method('getRequires')
            ->will($this->returnValue([$packageLink]));
        $package2->expects($this->any())
            ->method('getDevRequires')
            ->will($this->returnValue([$packageLink]));

        $package3 = $this->createPackageMock($expectedDependents[2]);
        $package3->expects($this->any())
            ->method('getRequires')
            ->will($this->returnValue([]));
        $package3->expects($this->any())
            ->method('getDevRequires')
            ->will($this->returnValue([$packageLink1]));

        $composer = $this->createComposerMock();
        $repositoryManager = $this->createRepositoryManagerMock();
        $localRepository = new WritableArrayRepository([$package1, $package2, $package3]);

        $composer->expects($this->any())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));
        $repositoryManager->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));

        $manager = $this->createPackageManager($composer);
        $dependents = $manager->getDependents($packageName);
        sort($expectedDependents);
        sort($dependents);

        $this->assertEquals($expectedDependents, $dependents);
    }

    /**
     * @test
     */
    public function shouldReturnListOfAvailableUpdates()
    {
        $expectedUpdates = [
            new PackageUpdate('vendor1/package1', 'v1 (asdf)', 'v1 (ffff)'),
            new PackageUpdate('vendor2/package2', 'v1 (asss)', 'v1 (dddd)'),
        ];
        $package1 = $this->createPackageMock('vendor1/package1');
        $package1->expects($this->any())->method('getPrettyVersion')->will($this->returnValue('v1'));
        $package1->expects($this->any())->method('getSourceReference')->will($this->returnValue('asdf'));

        $updatedPackage1 = $this->createPackageMock('vendor1/package1');
        $updatedPackage1->expects($this->any())->method('getNames')->will($this->returnValue(['vendor1/package1']));
        $updatedPackage1->expects($this->any())->method('getStability')->will($this->returnValue('stable'));
        $updatedPackage1->expects($this->any())->method('getPrettyVersion')->will($this->returnValue('v1'));
        $updatedPackage1->expects($this->any())->method('getVersion')->will($this->returnValue('1.0.0.0'));
        $updatedPackage1->expects($this->any())->method('getSourceReference')->will($this->returnValue('ffff'));

        $package2 = $this->createPackageMock('vendor2/package2');
        $package2->expects($this->any())->method('getPrettyVersion')->will($this->returnValue('v1'));
        $package2->expects($this->any())->method('getSourceReference')->will($this->returnValue('asss'));

        $updatedPackage2 = $this->createPackageMock('vendor2/package2');
        $updatedPackage2->expects($this->any())->method('getNames')->will($this->returnValue(['vendor2/package2']));
        $updatedPackage2->expects($this->any())->method('getStability')->will($this->returnValue('stable'));
        $updatedPackage2->expects($this->any())->method('getPrettyVersion')->will($this->returnValue('v1'));
        $updatedPackage2->expects($this->any())->method('getVersion')->will($this->returnValue('1.0.0.0'));
        $updatedPackage2->expects($this->any())->method('getSourceReference')->will($this->returnValue('dddd'));

        $package3 = $this->getPackage('vendor3/package3', 'v1');

        $composer = $this->createComposerMock();
        $repositoryManager = $this->createRepositoryManagerMock();
        $localRepository = new WritableArrayRepository([$package1, $package2, $package3]);
        $repository = new ArrayRepository([$updatedPackage1, $updatedPackage2, clone $package3]);
        $composer->expects($this->any())->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));
        $repositoryManager->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));
        $repositoryManager->expects($this->any())
            ->method('getRepositories')
            ->will($this->returnValue([$repository]));

        $manager = $this->createPackageManager($composer);

        $this->assertEquals($expectedUpdates, $manager->getAvailableUpdates());
    }

    /**
     * @test
     */
    public function shouldUpdatePackage()
    {
        $packageName = 'vendor1/package1';

        $package1 = $this->createPackageMock($packageName);
        $package1->expects($this->any())->method('getSourceReference')->will($this->returnValue('asdf'));

        $updatedPackage1 = $this->createPackageMock($packageName);
        $updatedPackage1->expects($this->any())->method('getSourceReference')->will($this->returnValue('ffff'));

        $package2 = $this->createPackageMock('vendor2/package2');
        $package2->expects($this->any())->method('getSourceReference')->will($this->returnValue('asss'));

        $updatedPackage2 = $this->createPackageMock('vendor2/package2');
        $updatedPackage2->expects($this->any())->method('getSourceReference')->will($this->returnValue('dddd'));

        $package3 = $this->getPackage('vendor3/package3', 'v1');
        $removedPackage = $this->getPackage('removed/removed', 'v1');
        $newInstalledPackage = $this->getPackage('new/package', 'v1');

        $composer = $this->createComposerMock();
        $repositoryManager = $this->createRepositoryManagerMock();
        $localRepository = $this->createLocalRepositoryMock();

        $composer->expects($this->any())->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));
        $repositoryManager->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));
        $localRepository->expects($this->at(0))
            ->method('getCanonicalPackages')
            ->will($this->returnValue([$package1, $package2, $removedPackage, $package3]));
        $localRepository->expects($this->at(1))
            ->method('getCanonicalPackages')
            ->will($this->returnValue([$updatedPackage1, $updatedPackage2, $package3, $newInstalledPackage]));
        $localRepository->expects($this->at(2))
            ->method('getCanonicalPackages')
            ->will($this->returnValue([$updatedPackage1, $updatedPackage2, $package3, $newInstalledPackage]));
        $localRepository->expects($this->exactly(2))
            ->method('findPackages')
            ->will($this->returnValue([$newInstalledPackage]));

        $runner = $this->createScriptRunnerMock();
        $runner->expects($this->once())
            ->method('runInstallScripts')
            ->with($this->isInstanceOf('Composer\Package\PackageInterface'));
        $runner->expects($this->exactly(2))
            ->method('runUpdateScripts')
            ->with($this->isInstanceOf('Composer\Package\PackageInterface'));
        $runner->expects($this->once())
            ->method('runUninstallScripts')
            ->with($this->isInstanceOf('Composer\Package\PackageInterface'));
        $runner->expects($this->once())
            ->method('clearDistApplicationCache');
        $runner->expects($this->once())
            ->method('runPlatformUpdate');

        $composerInstaller = $this->prepareInstallerMock($packageName, 0);

        $maintenance = $this->getEnableMaintenanceMock();

        $logger = $this->createLoggerMock();
        $logger->expects($this->at(0))
            ->method('info')
            ->with($this->stringContains('updating begin'));
        $logger->expects($this->at(1))
            ->method('info')
            ->with($this->stringContains('Updating composer.json'));
        $logger->expects($this->at(2))
            ->method('info')
            ->with($this->stringContains('Updating composer.json'));
        $logger->expects($this->at(3))
            ->method('info')
            ->with($this->stringContains('updated'));
        $logger->expects($this->never())
            ->method('error');

        $pathToComposerJson = $this->getPathToComposerJson(uniqid());

        $manager = $this->createPackageManager(
            $composer,
            $composerInstaller,
            null,
            $runner,
            $maintenance,
            $logger,
            $pathToComposerJson
        );
        $manager->update($packageName);
    }

    /**
     * @test
     *
     * @expectedException \Oro\Bundle\DistributionBundle\Exception\VerboseException
     * @expectedExceptionMessage vendor1/package1 can't be updated
     *
     */
    public function throwsExceptionIfDoInstallReturnsFalse()
    {
        $packageName = 'vendor1/package1';

        $composer = $this->createComposerMock();
        $repositoryManager = $this->createRepositoryManagerMock();
        $localRepository = new WritableArrayRepository([$this->getPackage($packageName, 1)]);

        $composer->expects($this->any())->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));
        $repositoryManager->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));

        $composerInstaller = $this->prepareInstallerMock($packageName, false);

        $bufferMock = $this->createBufferIoMock($bufferOutput = 'Some output');

        $logger = $this->createLoggerMock();
        $logger->expects($this->at(0))
            ->method('info')
            ->with($this->stringContains('updating begin'));
        $logger->expects($this->at(1))
            ->method('info')
            ->with($this->stringContains('Updating composer.json'));
        $logger->expects($this->at(2))
            ->method('info')
            ->with($this->stringContains('Updating composer.json'));
        $logger->expects($this->at(3))
            ->method('error')
            ->with($this->stringContains('can\'t be updated!'));
        $logger->expects($this->at(4))
            ->method('error')
            ->with($bufferOutput);

        $pathToComposerJson = $this->getPathToComposerJson(uniqid());

        $manager = $this->createPackageManager(
            $composer,
            $composerInstaller,
            $bufferMock,
            $this->createScriptRunnerMock(),
            null,
            $logger,
            $pathToComposerJson
        );
        $manager->update($packageName);
    }

    /**
     * @test
     */
    public function shouldReturnTrueIfUpdateIsAvailable()
    {
        $packageName = 'vendor1/package1';

        $package = $this->createPackageMock($packageName);
        $package->expects($this->any())->method('getSourceReference')->will($this->returnValue('asdf'));

        $updatedPackage = $this->createPackageMock($packageName);
        $updatedPackage->expects($this->any())->method('getNames')->will($this->returnValue([$packageName]));
        $updatedPackage->expects($this->any())->method('getStability')->will($this->returnValue('stable'));
        $updatedPackage->expects($this->any())->method('getVersion')->will($this->returnValue('1.0.0.0'));

        $composer = $this->createComposerMock();
        $repositoryManager = $this->createRepositoryManagerMock();
        $localRepository = new WritableArrayRepository([$package]);

        $repository = new ArrayRepository([$updatedPackage]);

        $composer->expects($this->any())->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));
        $repositoryManager->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));
        $repositoryManager->expects($this->any())
            ->method('getRepositories')
            ->will($this->returnValue([$repository]));

        $manager = $this->createPackageManager($composer);
        $this->assertTrue($manager->isUpdateAvailable($packageName));

    }

    /**
     * @test
     */
    public function shouldReturnFalseIfThereAreNoUpdatesForPackage()
    {
        $packageName = 'vendor1/package1';

        $package = $this->createPackageMock($packageName);
        $package->expects($this->any())->method('getNames')->will($this->returnValue([$packageName]));
        $package->expects($this->any())->method('getStability')->will($this->returnValue('stable'));
        $package->expects($this->any())->method('getVersion')->will($this->returnValue('1.0.0.0'));

        $composer = $this->createComposerMock();
        $repositoryManager = $this->createRepositoryManagerMock();
        $localRepository = new WritableArrayRepository([$package]);

        $repository = new ArrayRepository([clone $package]);

        $composer->expects($this->any())->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManager));
        $repositoryManager->expects($this->any())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepository));
        $repositoryManager->expects($this->any())
            ->method('getRepositories')
            ->will($this->returnValue([$repository]));

        $manager = $this->createPackageManager($composer);
        $this->assertFalse($manager->isUpdateAvailable($packageName));
    }

    /**
     * @param string $name
     * @param string $version
     * @param string $class
     *
     * @return PackageInterface
     */
    protected function getPackage($name, $version, $class = 'Composer\Package\Package')
    {
        static $parser;
        if (!$parser) {
            $parser = new VersionParser();
        }

        return new $class($name, $parser->normalize($version), $version);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Composer
     */
    protected function createComposerMock()
    {
        $composer = $this->createConstructorLessMock('Composer\Composer');
        $rootPackage = $this->createRootPackageMock();
        $composer->expects($this->any())
            ->method('getPackage')
            ->will($this->returnValue($rootPackage));
        $rootPackage->expects($this->any())
            ->method('getPreferStable')
            ->will($this->returnValue(true));

        return $composer;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RepositoryManager
     */
    protected function createRepositoryManagerMock()
    {
        return $this->createConstructorLessMock('Composer\Repository\RepositoryManager');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ComposerRepository
     */
    protected function createComposerRepositoryMock()
    {
        return $this->createConstructorLessMock('Composer\Repository\ComposerRepository');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Installer
     */
    protected function createComposerInstallerMock()
    {
        return $this->createConstructorLessMock('Composer\Installer');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Link
     */
    protected function createComposerPackageLinkMock()
    {
        return $this->createConstructorLessMock('Composer\Package\Link');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RootPackageInterface
     */
    protected function createRootPackageMock()
    {
        return $this->createConstructorLessMock('Composer\Package\RootPackageInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|BufferIO
     */
    protected function createComposerIO()
    {
        return new BufferIO();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Runner
     */
    protected function createScriptRunnerMock()
    {
        return $this->createConstructorLessMock('Oro\Bundle\DistributionBundle\Script\Runner');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|InstallationManager
     */
    protected function createInstallationManagerMock()
    {
        return $this->createConstructorLessMock('Composer\Installer\InstallationManager');
    }

    /**
     * @param Composer $composer
     * @param Installer $installer
     * @param BufferIO $io
     * @param Runner $scriptRunner
     * @param MaintenanceMode $maintenance
     * @param LoggerInterface $logger
     * @param null $pathToComposerJson
     *
     * @return PackageManager
     */
    protected function createPackageManager(
        Composer $composer = null,
        Installer $installer = null,
        BufferIO $io = null,
        Runner $scriptRunner = null,
        MaintenanceMode $maintenance = null,
        LoggerInterface $logger = null,
        $pathToComposerJson = null
    ) {
        if (!$composer) {
            $composer = $this->createComposerMock();
        }
        if (!$installer) {
            $installer = $this->createComposerInstallerMock();
        }
        if (!$io) {
            $io = $this->createComposerIO();
        }
        if (!$scriptRunner) {
            $scriptRunner = $this->createScriptRunnerMock();
        }
        if (!$maintenance) {
            $maintenance = $this->createMaintenanceMock();
        }
        if (!$logger) {
            $logger = $this->createLoggerMock();
        }
        if (!$pathToComposerJson) {
            $pathToComposerJson = $this->getPathToComposerJson();
        }
        return new PackageManager(
            $composer,
            $installer,
            $io,
            $scriptRunner,
            $maintenance,
            $logger,
            $pathToComposerJson
        );
    }

    /**
     * @param string $packageName
     * @param bool $runReturnValue
     *
     * @return Installer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareInstallerMock($packageName, $runReturnValue)
    {
        $composerInstaller = $this->createComposerInstallerMock();
        $composerInstaller->expects($this->once())->method('setDryRun')->with($this->equalTo(false))->will(
            $this->returnSelf()
        );
        $composerInstaller->expects($this->once())->method('setVerbose')->with($this->equalTo(false))->will(
            $this->returnSelf()
        );
        $composerInstaller->expects($this->once())->method('setPreferSource')->with($this->equalTo(false))->will(
            $this->returnSelf()
        );
        $composerInstaller->expects($this->once())->method('setPreferDist')->with($this->equalTo(true))->will(
            $this->returnSelf()
        );
        $composerInstaller->expects($this->once())->method('setDevMode')->with($this->equalTo(false))->will(
            $this->returnSelf()
        );
        $composerInstaller->expects($this->once())->method('setRunScripts')->with($this->equalTo(true))->will(
            $this->returnSelf()
        );
        $composerInstaller->expects($this->once())->method('setUpdate')->with($this->equalTo(true))->will(
            $this->returnSelf()
        );
        $composerInstaller->expects($this->once())->method('setUpdateWhitelist')->with(
            $this->equalTo([$packageName])
        )->will($this->returnSelf());
        $composerInstaller->expects($this->once())->method('setOptimizeAutoloader')->with(
            $this->equalTo(true)
        )->will($this->returnSelf());
        $composerInstaller->expects($this->once())->method('setOptimizeAutoloader')->with(
            $this->equalTo(true)
        )->will($this->returnSelf());

        $composerInstaller->expects($this->once())->method('run')->will($this->returnValue($runReturnValue));
        return $composerInstaller;
    }

    /**
     * @param $name
     * @return PackageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createPackageMock($name)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Package $package */
        $package = $this->getMock('Composer\Package\PackageInterface');
        $package->id = $name . uniqid();

        $package->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));

        return $package;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MaintenanceMode
     */
    protected function createMaintenanceMock()
    {
        return $this->getMockBuilder('Oro\Bundle\PlatformBundle\Maintenance\Mode')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLoggerMock()
    {
        return $this->getMock('Psr\Log\LoggerInterface');
    }

    /**
     * @param $bufferOutput
     * @return BufferIO|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createBufferIoMock($bufferOutput)
    {
        $bufferMock = $this->createConstructorLessMock('Composer\IO\BufferIO');
        $bufferMock->expects($this->any())
            ->method('getOutput')
            ->will($this->returnValue($bufferOutput));

        return $bufferMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|WritableRepositoryInterface
     */
    protected function createLocalRepositoryMock()
    {
        return $this->getMock('Composer\Repository\WritableRepositoryInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MaintenanceMode
     */
    protected function getEnableMaintenanceMock()
    {
        $maintenance = $this->createMaintenanceMock();
        $maintenance->expects($this->once())->method('activate');

        return $maintenance;
    }

    /**
     * @param   string $filename
     * @return  string
     */
    protected function getPathToComposerJson($filename = 'composer.json')
    {
        $pathToComposerJson = tempnam(sys_get_temp_dir(), $filename);
        file_put_contents($pathToComposerJson, '{}');

        return $pathToComposerJson;
    }
}
