<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Manager;

use Composer\Composer;
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
        $composerMock=$this->createComposerMock();
        $repositoryManagerMock=$this->createConstructorLessMock('Composer\Repository\RepositoryManager');
        $localRepositoryMock=$this->createConstructorLessMock('Composer\Repository\WritableRepositoryInterface');

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
     * @return \PHPUnit_Framework_MockObject_MockObject|Composer
     */
    protected function createComposerMock()
    {
        return $this->createConstructorLessMock('Composer\Composer');
    }
}
