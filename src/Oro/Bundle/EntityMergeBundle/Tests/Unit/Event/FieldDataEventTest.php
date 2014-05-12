<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Event;

use Oro\Bundle\EntityMergeBundle\Event\FieldDataEvent;

class FieldDataEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldData;

    /**
     * @var FieldDataEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->fieldData = $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\FieldData')
            ->disableOriginalConstructor()
            ->getMock();
        $this->event = new FieldDataEvent($this->fieldData);
    }

    public function testGetEntityData()
    {
        $this->assertEquals($this->fieldData, $this->event->getFieldData());
    }
}
