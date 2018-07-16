<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model;

use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Model\EntityMerger;

class EntityMergerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityMerger
     */
    protected $merger;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $eventDispatcher;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject[]
     */
    protected $steps;

    protected function setUp()
    {
        $this->steps = array(
            $this->createMock('Oro\Bundle\EntityMergeBundle\Model\Step\MergeStepInterface'),
            $this->createMock('Oro\Bundle\EntityMergeBundle\Model\Step\MergeStepInterface')
        );
        $this->eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->merger = new EntityMerger($this->steps, $this->eventDispatcher);
    }

    public function testMerge()
    {
        $data = $this->createEntityData();

        $this->steps[0]->expects($this->once())->method('run')->with($data);
        $this->steps[1]->expects($this->once())->method('run')->with($data);

        $this->eventDispatcher->expects($this->exactly(2))->method('dispatch');
        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(
                MergeEvents::BEFORE_MERGE_ENTITY,
                new EntityDataEvent($data)
            );

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(
                MergeEvents::AFTER_MERGE_ENTITY,
                new EntityDataEvent($data)
            );

        $this->merger->merge($data);
    }

    protected function createEntityData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
