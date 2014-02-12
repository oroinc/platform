<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Step;

use Oro\Bundle\EntityMergeBundle\Model\Step\MergeFieldsStep;

class MergeFieldsStepTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MergeFieldsStep
     */
    protected $step;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $strategy;

    protected function setUp()
    {
        $this->strategy = $this->getMock('Oro\Bundle\EntityMergeBundle\Model\Strategy\StrategyInterface');
        $this->step = new MergeFieldsStep($this->strategy);
    }

    public function testRun()
    {
        $data = $this->createEntityData();

                $fooField = $this->createFieldData();
        $barField = $this->createFieldData();

        $data->expects($this->once())
            ->method('getFields')
            ->will($this->returnValue(array($fooField, $barField)));

        $this->strategy->expects($this->exactly(2))->method('merge');

        $this->strategy->expects($this->at(0))
            ->method('merge')
            ->with($fooField);

        $this->strategy->expects($this->at(1))
            ->method('merge')
            ->with($barField);

        $this->step->run($data);
    }

    protected function createEntityData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createFieldData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\FieldData')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
