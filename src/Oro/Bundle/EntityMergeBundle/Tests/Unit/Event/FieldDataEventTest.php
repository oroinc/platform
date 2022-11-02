<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Event;

use Oro\Bundle\EntityMergeBundle\Data\FieldData;
use Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent;

class FieldDataEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var FieldData|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldData;

    /** @var FieldDataEvent */
    private $event;

    protected function setUp(): void
    {
        $this->fieldData = $this->createMock(FieldData::class);

        $this->event = new FieldDataEvent($this->fieldData);
    }

    public function testGetEntityData()
    {
        $this->assertEquals($this->fieldData, $this->event->getFieldData());
    }
}
