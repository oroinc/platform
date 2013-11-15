<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Manager;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Repository\RepositoryManager;
use Composer\Repository\WritableRepositoryInterface;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Oro\Bundle\DistributionBundle\Test\PhpUnit\Helper\MockHelperTrait;

class PackageManagerTest extends \PHPUnit_Framework_TestCase
{
    use MockHelperTrait;

    /**
     * @test
     */
    public function shouldBeConstructedWithRepositoryAndStorage()
    {
        new PackageManager($this->createComposerMock());
    }

    /**
     * @test
     */
    public function shouldReturnInstalledPackages()
    {
        $composerMock = $this->createComposerMock();
        $repositoryManagerMock = $this->createRepositoryManagerMock();
        $localRepositoryMock = $this->createLocalRepositoryMock();

        $composerMock->expects($this->once())
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManagerMock));

        $repositoryManagerMock->expects($this->once())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepositoryMock));

        $localRepositoryMock->expects($this->once())
            ->method('getCanonicalPackages')
            ->will($this->returnValue($installedPackages = [rand(), rand()]));

        $manager = new PackageManager($composerMock);
        $this->assertEquals($installedPackages, $manager->getInstalled());
    }

    /**
     * @test
     */
    public function shouldReturnAvailablePackages()
    {
        $composerMock = $this->createComposerMock();
        $repositoryManagerMock = $this->createRepositoryManagerMock();
        $localRepositoryMock = $this->createLocalRepositoryMock();

        $composerRepositoryMock = $this->createComposerRepositoryMock();
        $composerRepositoryWithoutProvidersMock = $this->createComposerRepositoryMock();
        $anyRepositoryExceptComposerRepositoryMock = $this->createConstructorLessMock(
            'Composer\Repository\ArrayRepository'
        );

        $installedPackageMock1 = $this->createCanonicalPackageMock();
        $installedPackageMock2 = $this->createCanonicalPackageMock();
        $installedPackageNames = ['name1', 'name2'];

        $availableProviderNames = ['name3', 'name4'];
        $availablePackageMock1 = $this->createCanonicalPackageMock();
        $availablePackageMock2 = $this->createCanonicalPackageMock();
        $availablePackageMock3 = $this->createCanonicalPackageMock();
        $availablePackageMock4 = $this->createCanonicalPackageMock();
        $availablePackageNames = ['name1', 'name5', 'name4', 'name6'];


        $composerMock->expects($this->exactly(2))
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManagerMock));

        $repositoryManagerMock->expects($this->once())
            ->method('getRepositories')
            ->will(
                $this->returnValue(
                    [
                        $composerRepositoryMock,
                        $composerRepositoryWithoutProvidersMock,
                        $anyRepositoryExceptComposerRepositoryMock
                    ]
                )
            )
        ;

        // Fetch already installed packages
        $repositoryManagerMock->expects($this->once())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepositoryMock));

        $localRepositoryMock->expects($this->once())
            ->method('getCanonicalPackages')
            ->will($this->returnValue([$installedPackageMock1, $installedPackageMock2]));

        $installedPackageMock1->expects($this->once())
            ->method('getPrettyName')
            ->will($this->returnValue($installedPackageNames[0]));

        $installedPackageMock2->expects($this->once())
            ->method('getPrettyName')
            ->will($this->returnValue($installedPackageNames[1]));

        // available packages configuration
        // from composer repo
        $composerRepositoryMock->expects($this->once())
            ->method('hasProviders')
            ->will($this->returnValue(true));

        $composerRepositoryMock->expects($this->once())
            ->method('getProviderNames')
            ->will($this->returnValue($availableProviderNames));

        // from composer repo without providers
        $availablePackageMock1->expects($this->once())
            ->method('getPrettyName')
            ->will($this->returnValue($availablePackageNames[0]));

        $availablePackageMock2->expects($this->once())
            ->method('getPrettyName')
            ->will($this->returnValue($availablePackageNames[1]));

        $composerRepositoryWithoutProvidersMock->expects($this->once())
            ->method('hasProviders')
            ->will($this->returnValue(false));

        $composerRepositoryWithoutProvidersMock->expects($this->once())
            ->method('getPackages')
            ->will($this->returnValue([$availablePackageMock1, $availablePackageMock2]));

        // from not composer repository
        $availablePackageMock3->expects($this->once())
            ->method('getPrettyName')
            ->will($this->returnValue($availablePackageNames[2]));

        $availablePackageMock4->expects($this->once())
            ->method('getPrettyName')
            ->will($this->returnValue($availablePackageNames[3]));

        $anyRepositoryExceptComposerRepositoryMock->expects($this->once())
            ->method('getPackages')
            ->will($this->returnValue([$availablePackageMock3, $availablePackageMock4]));

        $manager = new PackageManager($composerMock);

        $this->assertEquals(
            ['name3', 'name4', 'name5', 'name6'],
            $manager->getAvailable()
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Composer
     */
    protected function createComposerMock()
    {
        return $this->createConstructorLessMock('Composer\Composer');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RepositoryManager
     */
    protected function createRepositoryManagerMock()
    {
        return $this->createConstructorLessMock('Composer\Repository\RepositoryManager');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|WritableRepositoryInterface
     */
    protected function createLocalRepositoryMock()
    {
        return $this->createConstructorLessMock('Composer\Repository\WritableRepositoryInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PackageInterface
     */
    protected function createCanonicalPackageMock()
    {
        return $this->createConstructorLessMock('Composer\Package\PackageInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createComposerRepositoryMock()
    {
        return $this->createConstructorLessMock('Composer\Repository\ComposerRepository');
    }
}
