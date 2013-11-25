<?php
namespace Oro\Bundle\DistributionBundle\Tests\Unit\Entity;

use Oro\Bundle\DistributionBundle\Entity\PackageUpdate;

class PackageUpdateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructed()
    {
        new PackageUpdate('vendor/package', 'v1', 'v2');
    }

    /**
     * @test
     */
    public function shouldAllowToGetPropertiesSetViaConstructor()
    {
        $packageName = 'vendor/package';
        $currentVersion = 'v1';
        $upToDateVersion = 'v2';
        $pu=new PackageUpdate($packageName, $currentVersion, $upToDateVersion);

        $this->assertEquals($packageName, $pu->getPackageName());
        $this->assertEquals($currentVersion, $pu->getCurrentVersionString());
        $this->assertEquals($upToDateVersion, $pu->getUpToDateVersionString());
    }
}
 