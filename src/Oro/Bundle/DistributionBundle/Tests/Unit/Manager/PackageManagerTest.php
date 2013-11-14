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

        $composerRepositoryMock = $this->createConstructorLessMock('Composer\Repository\ComposerRepository');
        $anyRepositoryExceptComposerRepositoryMock = $this->createConstructorLessMock(
            'Composer\Repository\ArrayRepository'
        );
        $canonicalPackageMock1 = $this->createCanonicalPackageMock();
        $canonicalPackageMock2 = $this->createCanonicalPackageMock();
        $installedPackageNames = ['name1', 'name2'];
        $getProviderNamesReturnValue = ['name3', 'name4'];
        $getPackagesReturnValue = ['name1', 'name5'];

        $composerMock->expects($this->exactly(2))
            ->method('getRepositoryManager')
            ->will($this->returnValue($repositoryManagerMock));

        // installed packages configuration
        $repositoryManagerMock->expects($this->once())
            ->method('getRepositories')
            ->will($this->returnValue([$composerRepositoryMock, $anyRepositoryExceptComposerRepositoryMock]));

        $repositoryManagerMock->expects($this->once())
            ->method('getLocalRepository')
            ->will($this->returnValue($localRepositoryMock));

        $localRepositoryMock->expects($this->once())
            ->method('getCanonicalPackages')
            ->will($this->returnValue([$canonicalPackageMock1, $canonicalPackageMock2]));

        $canonicalPackageMock1->expects($this->once())
            ->method('getPrettyName')
            ->will($this->returnValue($installedPackageNames[0]));

        $canonicalPackageMock2->expects($this->once())
            ->method('getPrettyName')
            ->will($this->returnValue($installedPackageNames[1]));

        // available packages configuration
        $composerRepositoryMock->expects($this->once())
            ->method('getProviderNames')
            ->will($this->returnValue($getProviderNamesReturnValue));

        $anyRepositoryExceptComposerRepositoryMock->expects($this->once())
            ->method('getPackages')
            ->will($this->returnValue($getPackagesReturnValue));


        $manager = new PackageManager($composerMock);

        $this->assertEquals(
            ['name3', 'name4', 'name5'],
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
}
