<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;

class ValueRenderEventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var |\PHPUnit\Framework\MockObject\MockObject
     */
    protected $entity;

    /**
     * @var |\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldValue;

    /**
     * @var FieldConfigId|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldConfigId;

    protected function setUp()
    {
        $this->fieldConfigId = $this->getMockBuilder(FieldConfigId::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetterSetters()
    {
        $this->entity = new \stdClass();
        $this->fieldValue = 'testValue';
        $event = new ValueRenderEvent($this->entity, $this->fieldValue, $this->fieldConfigId);

        $this->assertEquals($this->entity, $event->getEntity());
        $this->assertEquals($this->fieldValue, $event->getFieldValue());
        $this->assertEquals($this->fieldConfigId, $event->getFieldConfigId());
        $this->assertTrue($event->isFieldVisible());
        $event->setFieldVisibility(false);
        $this->assertFalse($event->isFieldVisible());
    }
}
