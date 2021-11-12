<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Event;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;

class EntityDataEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityData|\PHPUnit\Framework\MockObject\MockObject */
    private $entityData;

    /** @var EntityDataEvent */
    private $event;

    protected function setUp(): void
    {
        $this->entityData = $this->createMock(EntityData::class);

        $this->event = new EntityDataEvent($this->entityData);
    }

    public function testGetEntityData()
    {
        $this->assertEquals($this->entityData, $this->event->getEntityData());
    }
}
