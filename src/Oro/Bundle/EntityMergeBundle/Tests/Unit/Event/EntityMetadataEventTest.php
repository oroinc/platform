<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Event;

use Oro\Bundle\EntityMergeBundle\Event\EntityMetadataEvent;

class EntityMetadataEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $entityMetadata;

    /**
     * @var EntityMetadataEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->entityMetadata = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\EntityMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = new EntityMetadataEvent($this->entityMetadata);
    }

    public function testGetEntityData()
    {
        $this->assertEquals($this->entityMetadata, $this->event->getEntityMetadata());
    }
}
