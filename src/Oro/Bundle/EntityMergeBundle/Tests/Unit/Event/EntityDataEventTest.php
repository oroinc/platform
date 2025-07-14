<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Event;

use Oro\Bundle\EntityMergeBundle\Data\EntityData;
use Oro\Bundle\EntityMergeBundle\Event\EntityDataEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityDataEventTest extends TestCase
{
    private EntityData&MockObject $entityData;
    private EntityDataEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityData = $this->createMock(EntityData::class);

        $this->event = new EntityDataEvent($this->entityData);
    }

    public function testGetEntityData(): void
    {
        $this->assertEquals($this->entityData, $this->event->getEntityData());
    }
}
