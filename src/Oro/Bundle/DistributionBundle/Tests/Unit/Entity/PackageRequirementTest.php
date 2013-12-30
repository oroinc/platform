<?php
namespace Oro\Bundle\DistributionBundle\Tests\Unit\Entity;

use Oro\Bundle\DistributionBundle\Entity\PackageRequirement;

class PackageRequirementTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructed()
    {
        new PackageRequirement('vendor/package', false);
    }

    /**
     * @test
     */
    public function shouldAllowToGetPropertiesSetViaConstructor()
    {
        $name = 'vendor/package';
        $installed = true;
        $pr = new PackageRequirement($name, $installed);

        $this->assertEquals($name, $pr->getName());
        $this->assertEquals($installed, $pr->isInstalled());
    }

    /**
     * @test
     */
    public function shouldReturnArrayOfObjectProperties()
    {
        $pr = new PackageRequirement('name', true);
        $expectedResult = [
            'name' => 'name',
            'installed' => true
        ];

        $this->assertEquals($expectedResult, $pr->toArray());
    }
}
