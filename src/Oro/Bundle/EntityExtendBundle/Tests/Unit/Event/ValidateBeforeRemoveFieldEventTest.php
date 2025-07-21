<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Event\ValidateBeforeRemoveFieldEvent;
use PHPUnit\Framework\TestCase;

class ValidateBeforeRemoveFieldEventTest extends TestCase
{
    public function testGetterSetters(): void
    {
        $fieldConfigModel = new FieldConfigModel('color');

        $event = new ValidateBeforeRemoveFieldEvent($fieldConfigModel);

        $this->assertSame($fieldConfigModel, $event->getFieldConfig());
        $this->assertEquals([], $event->getValidationMessages());

        $event->addValidationMessage('message');
        $event->addValidationMessage('other message');

        $this->assertEquals(['message', 'other message'], $event->getValidationMessages());
    }
}
