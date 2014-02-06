<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model;

use Oro\Bundle\EntityMergeBundle\Event\AfterMergeEvent;
use Oro\Bundle\EntityMergeBundle\Event\BeforeMergeEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;

use Oro\Bundle\EntityMergeBundle\Model\EntityMerger;

class FieldMergerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityMerger $merger;
     */
    protected $merger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldMerger;

    protected function setup()
    {
        $this->fieldMerger = $this->getMock('Oro\Bundle\EntityMergeBundle\Model\FieldMerger\FieldMergerInterface');
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->merger = new EntityMerger($this->fieldMerger, $this->eventDispatcher);
    }

    public function testMerge()
    {
        $fooEntity = $this->createTestEntity(1);
        $barEntity = $this->createTestEntity(2);

        $data = $this->createEntityData();

        $data->expects($this->once())
            ->method('getEntities')
            ->will($this->returnValue(array($fooEntity, $barEntity)));

        $data->expects($this->once())
            ->method('getMasterEntity')
            ->will($this->returnValue($fooEntity));

        $fooField = $this->createFieldData();
        $barField = $this->createFieldData();

        $data->expects($this->once())
            ->method('getFields')
            ->will($this->returnValue(array($fooField, $barField)));

        $this->fieldMerger->expects($this->exactly(2))->method('merge');

        $this->fieldMerger->expects($this->at(0))
            ->method('merge')
            ->with($fooField);

        $this->fieldMerger->expects($this->at(1))
            ->method('merge')
            ->with($barField);

        $this->eventDispatcher->expects($this->exactly(2))->method('dispatch');
        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(
                MergeEvents::BEFORE_MERGE,
                new BeforeMergeEvent($data)
            );

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(
                MergeEvents::AFTER_MERGE,
                new AfterMergeEvent($data)
            );

        $this->merger->merge($data);
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

    protected function createTestEntity($id)
    {
        $result = new \stdClass();
        $result->id = $id;
        return $result;
    }
}
