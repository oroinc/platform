<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Step;

use Oro\Bundle\EntityMergeBundle\Model\Step\StepSorter;

class StepSorterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getOrderedStepsDataProvider
     */
    public function testGetOrderedSteps(array $actualSteps, array $expectedSteps)
    {
        $steps = StepSorter::getOrderedSteps($actualSteps);
        $actual = array_map(array($this, 'getClassName'), $steps);
        $expected = array_map(array($this, 'getClassName'), $expectedSteps);

        $this->assertEquals($expected, $actual);
    }

    public function getOrderedStepsDataProvider()
    {
        return array(
            'empty' => array(
                'actual' => array(),
                'expected' => array(),
            ),
            'without_dependencies' => array(
                'actual' => array(
                    $foo = $this->createMockStep('OroEntityMergeBundle_Tests_FooStep_1'),
                    $bar = $this->createMockStep('OroEntityMergeBundle_Tests_BarStep_1'),
                ),
                'expected' => array($foo, $bar),
            ),
            'with_dependencies_baz_foo_bar' => array(
                'actual' => array(
                    $foo = $this->createMockDependentStep(
                        'OroEntityMergeBundle_Tests_FooStep_2',
                        array('OroEntityMergeBundle_Tests_BazStep_2')
                    ),
                    $bar = $this->createMockDependentStep(
                        'OroEntityMergeBundle_Tests_BarStep_2',
                        array('OroEntityMergeBundle_Tests_FooStep_2')
                    ),
                    $baz = $this->createMockStep(
                        'OroEntityMergeBundle_Tests_BazStep_2'
                    ),
                ),
                'expected' => array($baz, $foo, $bar),
            ),
            'with_dependencies_baz_bar_foo' => array(
                'actual' => array(
                    $foo = $this->createMockDependentStep(
                        'OroEntityMergeBundle_Tests_FooStep_3',
                        array('OroEntityMergeBundle_Tests_BarStep_3')
                    ),
                    $bar = $this->createMockDependentStep(
                        'OroEntityMergeBundle_Tests_BarStep_3',
                        array('OroEntityMergeBundle_Tests_BazStep_3')
                    ),
                    $baz = $this->createMockStep(
                        'OroEntityMergeBundle_Tests_BazStep_3'
                    ),
                ),
                'expected' => array($baz, $bar, $foo),
            ),
        );
    }

    protected function createMockStep($mockClassName)
    {
        return $this->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Model\\Step\\MergeStepInterface')
            ->setMockClassName($mockClassName)
            ->getMock();
    }

    protected function createMockDependentStep($mockClassName, array $dependencies = array())
    {
        $result = $this->getMockBuilder('Oro\\Bundle\\EntityMergeBundle\\Model\\Step\\DependentMergeStepInterface')
            ->setMockClassName($mockClassName)
            ->getMock();

        $result->expects($this->once())
            ->method('getDependentSteps')
            ->will($this->returnValue($dependencies));

        return $result;
    }

    public function getClassName($object)
    {
        return get_class($object);
    }
}
