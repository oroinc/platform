<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;
use PHPUnit\Framework\TestCase;

class ValueRenderEventTest extends TestCase
{
    public function testGetterSetters(): void
    {
        $entity = new \stdClass();
        $fieldValue = 'testValue';
        $fieldConfigId = $this->createMock(FieldConfigId::class);
        $event = new ValueRenderEvent($entity, $fieldValue, $fieldConfigId);

        $this->assertEquals($entity, $event->getEntity());
        $this->assertEquals($fieldValue, $event->getFieldValue());
        $this->assertEquals($fieldConfigId, $event->getFieldConfigId());
        $this->assertTrue($event->isFieldVisible());
        $event->setFieldVisibility(false);
        $this->assertFalse($event->isFieldVisible());
    }
}
