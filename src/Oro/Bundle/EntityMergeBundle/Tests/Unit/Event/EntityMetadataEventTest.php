<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Event;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;
use Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata;

class EntityMetadataEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityMetadata|\PHPUnit\Framework\MockObject\MockObject */
    private $entityMetadata;

    /** @var EntityMetadataEvent */
    private $event;

    protected function setUp(): void
    {
        $this->entityMetadata = $this->createMock(EntityMetadata::class);

        $this->event = new EntityMetadataEvent($this->entityMetadata);
    }

    public function testGetEntityData()
    {
        $this->assertEquals($this->entityMetadata, $this->event->getEntityMetadata());
    }
}
