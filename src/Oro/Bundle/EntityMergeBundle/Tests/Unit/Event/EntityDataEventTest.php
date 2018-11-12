<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Event;

use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;

class EntityDataEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityData;

    /**
     * @var EntityDataEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->entityData = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = new EntityDataEvent($this->entityData);
    }

    public function testGetEntityData()
    {
        $this->assertEquals($this->entityData, $this->event->getEntityData());
    }
}
