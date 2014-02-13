<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model;

use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;
use Oro\Bundle\EntityMergeBundle\MergeEvents;
use Oro\Bundle\EntityMergeBundle\Model\EntityMerger;

class StrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityMerger
     */
    protected $merger;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject[]
     */
    protected $steps;

    protected function setUp()
    {
        $this->steps = array(
            $this->getMock('Oro\Bundle\EntityMergeBundle\Model\Step\MergeStepInterface'),
            $this->getMock('Oro\Bundle\EntityMergeBundle\Model\Step\MergeStepInterface')
        );
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
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
                MergeEvents::BEFORE_MERGE,
                new EntityDataEvent($data)
            );

        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(
                MergeEvents::AFTER_MERGE,
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
