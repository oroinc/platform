<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Entity;

use Oro\Bundle\DistributionBundle\Entity\Bundle;
use Oro\Bundle\DistributionBundle\Entity\Package;

class PackageTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @test
     */
    public function couldBeConstructedWithoutArgs()
    {
        new Package();
    }

    /**
     * @test
     *
     * @dataProvider provideSetterDataset
     */
    public function shouldAllowToSetProperty($setter, $value)
    {
        $package = new Package();
        $package->{$setter}($value);
    }

    /**
     * @test
     *
     * @dataProvider provideSetterGetterDataset
     */
    public function shouldReturnValueThatWasSetBefore($getter, $setter, $value)
    {
        $package = new Package();
        $package->{$setter}($value);

        $this->assertEquals($value, $package->{$getter}());
    }

    /**
     * @test
     */
    public function shouldReturnEmptyListOfBundles()
    {
        $package = new Package();

        $this->assertEquals([], $package->getBundles());
    }

    /**
     * @test
     */
    public function shouldAllowToAddBundle()
    {
        $package = new Package();
        $package->addBundle(new Bundle());
    }

    /**
     * @test
     */
    public function shouldReturnBundlesThatWereAddedBefore()
    {
        $package = new Package();
        $package->addBundle($bundle1 = new Bundle());
        $package->addBundle($bundle2 = new Bundle());

        $this->assertEquals([$bundle1, $bundle2], $package->getBundles());
    }

    /**
     * @test
     */
    public function shouldNotAddDuplicatedBundle()
    {
        $package = new Package();
        $bundle1 = new Bundle();
        $package->addBundle($bundle1);
        $package->addBundle($bundle1);

        $this->assertNotEquals([$bundle1, $bundle1], $package->getBundles());
    }

    /**
     * @return array
     */
    public static function provideSetterGetterDataset()
    {
        return [
            ['getVersion', 'setVersion', '2.0'],
            ['getDescription', 'setDescription', 'My package'],
            ['getName', 'setName', 'my/package'],
        ];
    }

    /**
     * @return array
     */
    public static function provideSetterDataset()
    {
        return [
            ['setVersion', '2.0'],
            ['setDescription', 'My package'],
            ['setName', 'my/package'],
        ];
    }
}
 