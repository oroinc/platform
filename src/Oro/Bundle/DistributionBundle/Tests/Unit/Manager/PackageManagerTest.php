<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Manager;

use Composer\Composer;
use Composer\Installer;
use Composer\IO\BufferIO;
use Composer\IO\IOInterface;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Repository\ArrayRepository;
use Composer\Repository\ComposerRepository;
use Composer\Repository\PlatformRepository;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableArrayRepository;
use Composer\Installer\InstallationManager;
use Oro\Bundle\DistributionBundle\Entity\PackageRequirement;
use Oro\Bundle\DistributionBundle\Entity\PackageUpdate;
use Oro\Bundle\DistributionBundle\Exception\VerboseException;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\MockHelperTrait;
use Oro\Bundle\DistributionBundle\Script\Runner;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\ReflectionHelperTrait;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
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
        $availablePackage2 = $this->getPackage($duplicatedPackageName, 1);
        $composerRepositoryMock->expects($this->any())
            ->method('hasProviders')
            ->will($this->returnValue(true));

        $composerRepositoryMock->expects($this->once())
            ->method('getProviderNames')
            ->will($this->returnValue(['name1', 'name2']));

        $composerRepositoryMock->expects($this->any())
            ->method('whatProvides')
            ->will($this->returnValue([$availablePackage1, $this->getPackage('name2', 1)]));

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

        $composerRepositoryWithoutProvidersMock->expects($this->once())
            ->method('getPackages')
            ->will($this->returnValue([$availablePackage1, $availablePackage2]));
        $composerRepositoryWithoutProvidersMock->expects($this->any())
            ->method('getMinimalPackages')
            ->will($this->returnValue([]));

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
        $this->assertEquals(
            ['name2', $duplicatedPackageName, 'name4'],
            $packages
        );
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

        $installedPackage = $this->getPackage('installed/package', 1);

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
        $packageMock = $this->createPackageMock();
        $packageMock->expects($this->once())
            ->method('getRequires')
            ->will($this->returnValue([$requirementLinkMock1, $requirementLinkMock2, $platformRequirementLinkMock]));
        $packageMock->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($packageName));
        $packageMock->expects($this->any())
            ->method('getVersion')
            ->will($this->returnValue($packageVersion));
        $packageMock->expects($this->once())
            ->method('getNames')
            ->will($this->returnValue([$packageName]));
        $packageMock->expects($this->once())
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
        $localRepository = new WritableArrayRepository($installedPackages = [$newPackage]);

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
        $runner = $this->createScriptRunnerMock();
        $runner->expects($this->once())
            ->method('loadFixtures');

        $manager = $this->createPackageManager($composer, $composerInstaller, null, $runner, null, $tempComposerJson);
        $manager->install($newPackage->getName());

        $updatedComposerData = json_decode(file_get_contents($tempComposerJson), true);
        unlink($tempComposerJson);

        $this->assertEquals($expectedJsonData, $updatedComposerData);
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
        $localRepository = new WritableArrayRepository($installedPackages = [$newPackage]);

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
        $manager = $this->createPackageManager($composer, $composerInstaller, null, null, null, $tempComposerJson);

        try {
            $manager->install($newPackage->getName());
        } catch (VerboseException $e) {
            $composerDataAfterFail = json_decode(file_get_contents($tempComposerJson), true);
            unlink($tempComposerJson);

            $this->assertEquals("{$newPackageName} can't be installed!", $e->getMessage());
            return $this->assertEquals($composerJsonData, $composerDataAfterFail);
        }

        unlink($tempComposerJson);
        $this->fail('Exception wasn\'t caught');
    }

    /**
     * @test
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

        // Package repositories
        $repositoryManager->expects($this->once())
            ->method('getRepositories')
            ->will($this->returnValue([new WritableArrayRepository($installedPackages = [$newPackage])]));

        /** @var \PHPUnit_Framework_MockObject_MockObject $rootPackageMock */
        $rootPackageMock = $composer->getPackage();

        $composer->expects($this->once())
            ->method('getPackage')
            ->will($this->returnValue($rootPackageMock));

        $scriptRunner = $this->createScriptRunnerMock();
        $scriptRunner->expects($this->any())
            ->method('install')
            ->will($this->throwException($thrownException = new \Exception));

        $composerInstaller = $this->prepareInstallerMock($newPackage->getName(), 0);
        $manager = $this->createPackageManager($composer, $composerInstaller, null, $scriptRunner, null, $tempComposerJson);

        try {
            $manager->install($newPackage->getName());
        } catch (\Exception $e) {
            $composerDataAfterFail = json_decode(file_get_contents($tempComposerJson), true);
            unlink($tempComposerJson);

            $this->assertSame($thrownException, $e);
            return $this->assertEquals($composerJsonData, $composerDataAfterFail);
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

        $repository = new ArrayRepository(
            [
                $package1 = $this->getPackage('my/package', '1'),
                $package2 = $this->getPackage('my/package', '2'),
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
        $repository = new ArrayRepository(
            [
                $outdatedPackage = $this->getPackage($packageName, '1'),
                $freshPackage = $this->getPackage($packageName, '2'),
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
            ->method('uninstall')
            ->with($installedPackages[0]);
        $runner->expects($this->at(1))
            ->method('uninstall')
            ->with($installedPackages[1]);
        $runner->expects($this->once())
            ->method('removeCachedFiles');

        // Ready Steady Go!
        $manager = $this->createPackageManager($composer, null, null, $runner, null, $tempComposerJson);
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
        $manager = $this->createPackageManager();
        $manager->uninstall(['oro/platform']);
    }

    /**
     * @test
     *
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Package oro/platform-dist is not deletable
     */
    public function throwsExceptionWhenTryingToUninstallPlatformDist()
    {
        $manager = $this->createPackageManager();
        $manager->uninstall(['oro/platform-dist']);
    }

    /**
     * @test
     */
    public function shouldNotAllowToDeletePlatformAndPlatformDist()
    {
        $manager = $this->createPackageManager();

        $this->assertFalse($manager->canBeDeleted('oro/platform'));
        $this->assertFalse($manager->canBeDeleted('oro/platform-dist'));
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

        $package1 = $this->createPackageMock();
        $package1->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($expectedDependents[0]));
        $package1->expects($this->any())
            ->method('getRequires')
            ->will($this->returnValue([$packageLink]));
        $package1->expects($this->any())
            ->method('getDevRequires')
            ->will($this->returnValue([]));

        $package2 = $this->createPackageMock();
        $package2->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($expectedDependents[1]));
        $package2->expects($this->any())
            ->method('getRequires')
            ->will($this->returnValue([$packageLink]));
        $package2->expects($this->any())
            ->method('getDevRequires')
            ->will($this->returnValue([$packageLink]));

        $package3 = $this->createPackageMock();
        $package3->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($expectedDependents[2]));
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
        $package1 = $this->createPackageMock();
        $package1->expects($this->any())->method('getName')->will($this->returnValue('vendor1/package1'));
        $package1->expects($this->any())->method('getPrettyVersion')->will($this->returnValue('v1'));
        $package1->expects($this->any())->method('getSourceReference')->will($this->returnValue('asdf'));

        $updatedPackage1 = $this->createPackageMock();
        $updatedPackage1->expects($this->any())->method('getName')->will($this->returnValue('vendor1/package1'));
        $updatedPackage1->expects($this->any())->method('getNames')->will($this->returnValue(['vendor1/package1']));
        $updatedPackage1->expects($this->any())->method('getStability')->will($this->returnValue('stable'));
        $updatedPackage1->expects($this->any())->method('getPrettyVersion')->will($this->returnValue('v1'));
        $updatedPackage1->expects($this->any())->method('getVersion')->will($this->returnValue('1.0.0.0'));
        $updatedPackage1->expects($this->any())->method('getSourceReference')->will($this->returnValue('ffff'));

        $package2 = $this->createPackageMock();
        $package2->expects($this->any())->method('getName')->will($this->returnValue('vendor2/package2'));
        $package2->expects($this->any())->method('getPrettyVersion')->will($this->returnValue('v1'));
        $package2->expects($this->any())->method('getSourceReference')->will($this->returnValue('asss'));

        $updatedPackage2 = $this->createPackageMock();
        $updatedPackage2->expects($this->any())->method('getName')->will($this->returnValue('vendor2/package2'));
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

        $package1 = $this->createPackageMock();
        $package1->expects($this->any())->method('getName')->will($this->returnValue($packageName));
        $package1->expects($this->any())->method('getSourceReference')->will($this->returnValue('asdf'));

        $updatedPackage1 = $this->createPackageMock();
        $updatedPackage1->expects($this->any())->method('getName')->will($this->returnValue($packageName));
        $updatedPackage1->expects($this->any())->method('getSourceReference')->will($this->returnValue('ffff'));

        $package2 = $this->createPackageMock();
        $package2->expects($this->any())->method('getName')->will($this->returnValue('vendor2/package2'));
        $package2->expects($this->any())->method('getSourceReference')->will($this->returnValue('asss'));

        $updatedPackage2 = $this->createPackageMock();
        $updatedPackage2->expects($this->any())->method('getName')->will($this->returnValue('vendor2/package2'));
        $updatedPackage2->expects($this->any())->method('getSourceReference')->will($this->returnValue('dddd'));

        $package3 = $this->getPackage('vendor3/package3', 'v1');
        $removedPackage = $this->getPackage('removed/removed', 'v1');
        $newInstalledPackage = $this->getPackage('new/package', 'v1');

        $composer = $this->createComposerMock();
        $repositoryManager = $this->createRepositoryManagerMock();
        $localRepository = $this->createConstructorLessMock('Composer\Repository\WritableRepositoryInterface');

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
            ->method('install')
            ->with($this->isInstanceOf('Composer\Package\PackageInterface'));
        $runner->expects($this->exactly(2))
            ->method('update')
            ->with($this->isInstanceOf('Composer\Package\PackageInterface'));
        $runner->expects($this->once())
            ->method('uninstall')
            ->with($this->isInstanceOf('Composer\Package\PackageInterface'));

        $composerInstaller = $this->prepareInstallerMock($packageName, 0);
        $manager = $this->createPackageManager(
            $composer,
            $composerInstaller,
            null,
            $runner,
            null,
            tempnam(sys_get_temp_dir(), uniqid())
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

        $manager = $this->createPackageManager(
            $composer,
            $composerInstaller,
            new BufferIO(),
            $this->createScriptRunnerMock(),
            null,
            tempnam(sys_get_temp_dir(), uniqid())
        );
        $manager->update($packageName);
    }

    /**
     * @test
     */
    public function shouldReturnTrueIfUpdateIsAvailable()
    {
        $packageName = 'vendor1/package1';

        $package = $this->createPackageMock();
        $package->expects($this->any())->method('getName')->will($this->returnValue($packageName));
        $package->expects($this->any())->method('getSourceReference')->will($this->returnValue('asdf'));

        $updatedPackage = $this->createPackageMock();
        $updatedPackage->expects($this->any())->method('getName')->will($this->returnValue($packageName));
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

        $package = $this->createPackageMock();
        $package->expects($this->any())->method('getName')->will($this->returnValue($packageName));
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
     * @return \PHPUnit_Framework_MockObject_MockObject|IOInterface
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
     * @param IOInterface $io
     * @param Runner $scriptRunner
     * @param null $pathToComposerJson
     *
     * @return PackageManager
     */
    protected function createPackageManager(
        Composer $composer = null,
        Installer $installer = null,
        IOInterface $io = null,
        Runner $scriptRunner = null,
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
        if(!$logger)
        {
            $logger = $this->createLoggerMock();
        }
        return new PackageManager($composer, $installer, $io, $scriptRunner, $logger, $pathToComposerJson);
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
     * @return \PHPUnit_Framework_MockObject_MockObject|PackageInterface
     */
    protected function createPackageMock()
    {
        return $this->getMock('Composer\Package\PackageInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLoggerMock()
    {
        return $this->getMock('Psr\Log\LoggerInterface');
    }
}
