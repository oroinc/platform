<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Entity;


use Oro\Bundle\DistributionBundle\Entity\Bundle;

class BundleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function couldBeConstructedWithoutArgs()
    {
        new Bundle();
    }

    /**
     * @test
     *
     * @dataProvider provideSetterDataset
     */
    public function shouldAllowToSetProperty($setter, $value)
    {
        $bundle = new Bundle();
        $bundle->{$setter}($value);
    }

    /**
     * @test
     *
     * @dataProvider provideSetterGetterDataset
     */
    public function shouldReturnValueThatWasSetBefore($getter, $setter, $value)
    {
        $bundle = new Bundle();
        $bundle->{$setter}($value);

        $this->assertEquals($value, $bundle->{$getter}());
    }


    /**
     * @return array
     */
    public static function provideSetterGetterDataset()
    {
        return [
            ['getEnabled', 'setEnabled', true],
            ['getKernel', 'setKernel', true],
            ['getName', 'setName', 'Some\Bundle\Name'],
            ['getPriority', 'setPriority', 7.6],
            ['getDependencies', 'setDependencies', ['dependency1', 'dependency2']],
        ];
    }

    /**
     * @return array
     */
    public static function provideSetterDataset()
    {
        return [
            ['setEnabled', true],
            ['setKernel', true],
            ['setName', 'Some\Bundle\Name'],
            ['setPriority', 7.6],
            ['setDependencies', ['dependency']],
        ];
    }
}
