<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit\Entity;

use Oro\Bundle\DistributionBundle\Entity\Bundle;

class BundleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideSetterDataset
     */
    public function testShouldAllowToSetProperty(string $setter, mixed $value)
    {
        $bundle = new Bundle();
        $bundle->{$setter}($value);
    }

    /**
     * @dataProvider provideSetterGetterDataset
     */
    public function testShouldReturnValueThatWasSetBefore(string $getter, string $setter, mixed $value)
    {
        $bundle = new Bundle();
        $bundle->{$setter}($value);

        $this->assertEquals($value, $bundle->{$getter}());
    }

    public static function provideSetterGetterDataset(): array
    {
        return [
            ['getEnabled', 'setEnabled', true],
            ['getKernel', 'setKernel', true],
            ['getName', 'setName', 'Some\Bundle\Name'],
            ['getPriority', 'setPriority', 7.6],
            ['getDependencies', 'setDependencies', ['dependency1', 'dependency2']],
        ];
    }

    public static function provideSetterDataset(): array
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
