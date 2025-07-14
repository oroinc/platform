<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Event;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldDataEventTest extends TestCase
{
    private FieldData&MockObject $fieldData;
    private FieldDataEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->fieldData = $this->createMock(FieldData::class);

        $this->event = new FieldDataEvent($this->fieldData);
    }

    public function testGetEntityData(): void
    {
        $this->assertEquals($this->fieldData, $this->event->getFieldData());
    }
}
