<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Event\ValidateBeforeRemoveFieldEvent;

class ValidateBeforeRemoveFieldEventTest extends \PHPUnit\Framework\TestCase
{
    public function testGetterSetters()
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
