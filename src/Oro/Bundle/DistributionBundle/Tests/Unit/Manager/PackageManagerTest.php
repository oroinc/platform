<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Manager;

use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Oro\Bundle\DistributionBundle\Repository\PackageRepository;
use Oro\Bundle\DistributionBundle\Storage\PackageStorage;

class PackageManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructedWithRepositoryAndStorage()
    {
        new PackageManager($this->createRepositoryMock(), $this->createStorageMock());
    }
    /**
     * @test
     */
    public function shouldReturnInstalledPackages()
    {
        $manager = new PackageManager($this->createRepositoryMock(), $this->createStorageMock());
        $installedPackages = $manager->getInstalled();

        $this->assertInternalType('array', $installedPackages);
        $this->assertCount(4, $installedPackages);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PackageRepository
     */
    public function createRepositoryMock()
    {
        return $this->getMock('Oro\Bundle\DistributionBundle\Repository\PackageRepository');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PackageStorage
     */
    public function createStorageMock()
    {
        return $this->getMock('Oro\Bundle\DistributionBundle\Storage\PackageStorage');
    }
}
