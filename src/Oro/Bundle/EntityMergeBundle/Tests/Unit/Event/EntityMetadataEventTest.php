<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Event;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityMetadataEventTest extends TestCase
{
    private EntityMetadata&MockObject $entityMetadata;
    private EntityMetadataEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityMetadata = $this->createMock(EntityMetadata::class);

        $this->event = new EntityMetadataEvent($this->entityMetadata);
    }

    public function testGetEntityData(): void
    {
        $this->assertEquals($this->entityMetadata, $this->event->getEntityMetadata());
    }
}
