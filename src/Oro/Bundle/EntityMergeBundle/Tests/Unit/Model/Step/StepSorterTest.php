<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Step;

use Oro\Bundle\EntityMergeBundle\Model\Step\DependentMergeStepInterface;
use Oro\Bundle\EntityMergeBundle\Model\Step\MergeStepInterface;
use Oro\Bundle\EntityMergeBundle\Model\Step\StepSorter;

class StepSorterTest extends \PHPUnit\Framework\TestCase
{
    private function createMockStep($mockClassName): MergeStepInterface
    {
        return $this->getMockBuilder(MergeStepInterface::class)
            ->setMockClassName($mockClassName)
            ->getMock();
    }

    private function createMockDependentStep(
        string $mockClassName,
        array $dependencies = []
    ): DependentMergeStepInterface {
        $result = $this->getMockBuilder(DependentMergeStepInterface::class)
            ->setMockClassName($mockClassName)
            ->getMock();
        $result->expects($this->once())
            ->method('getDependentSteps')
            ->willReturn($dependencies);

        return $result;
    }

    private function assertStepsEqual(array $expectedSteps, array $steps): void
    {
        $actual = array_map('get_class', $steps);
        $expected = array_map('get_class', $expectedSteps);
        $this->assertEquals($expected, $actual);
    }

    public function testGetOrderedStepsWhenNoSteps()
    {
        $this->assertSame([], StepSorter::getOrderedSteps([]));
    }

    public function testGetOrderedStepsForStepsWithoutDependencies()
    {
        $foo = $this->createMockStep('OroEntityMergeBundle_Tests_Foo_1');
        $bar = $this->createMockStep('OroEntityMergeBundle_Tests_Bar_1');

        $steps = StepSorter::getOrderedSteps([$foo, $bar]);
        $this->assertStepsEqual([$foo, $bar], $steps);
    }

    public function testGetOrderedStepsForStepsWithDependencies()
    {
        $foo = $this->createMockDependentStep(
            'OroEntityMergeBundle_Tests_Foo_2',
            ['OroEntityMergeBundle_Tests_Bar_2']
        );
        $bar = $this->createMockDependentStep(
            'OroEntityMergeBundle_Tests_Bar_2',
            ['OroEntityMergeBundle_Tests_Baz_2']
        );
        $baz = $this->createMockStep('OroEntityMergeBundle_Tests_Baz_2');

        $steps = StepSorter::getOrderedSteps([$foo, $bar, $baz]);
        $this->assertStepsEqual([$baz, $bar, $foo], $steps);
    }

    public function testGetOrderedStepsForStepsWithCyclicDependencies()
    {
        $foo = $this->createMockDependentStep(
            'OroEntityMergeBundle_Tests_Foo_3',
            ['OroEntityMergeBundle_Tests_Baz_3']
        );
        $bar = $this->createMockDependentStep(
            'OroEntityMergeBundle_Tests_Bar_3',
            ['OroEntityMergeBundle_Tests_Foo_3']
        );
        $baz = $this->createMockStep('OroEntityMergeBundle_Tests_Baz_3');

        $steps = StepSorter::getOrderedSteps([$foo, $bar, $baz]);
        $this->assertStepsEqual([$baz, $foo, $bar], $steps);
    }
}
