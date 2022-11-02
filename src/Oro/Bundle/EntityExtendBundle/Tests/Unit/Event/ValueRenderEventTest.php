<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Event\ValueRenderEvent;

class ValueRenderEventTest extends \PHPUnit\Framework\TestCase
{
    /** @var |\PHPUnit\Framework\MockObject\MockObject */
    private $entity;

    /** @var |\PHPUnit\Framework\MockObject\MockObject */
    private $fieldValue;

    /** @var FieldConfigId|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldConfigId;

    protected function setUp(): void
    {
        $this->fieldConfigId = $this->createMock(FieldConfigId::class);
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
