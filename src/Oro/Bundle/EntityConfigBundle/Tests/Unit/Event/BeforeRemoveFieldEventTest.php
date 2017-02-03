<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Event\BeforeRemoveFieldEvent;

class BeforeRemoveFieldEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $event = new BeforeRemoveFieldEvent('className', 'fieldName');

        $this->assertEquals('className', $event->getClassName());
        $this->assertEquals('fieldName', $event->getFieldName());

        $this->assertEquals([], $event->getValidationMessages());

        $event->addValidationMessage('Validation Message');

        $this->assertEquals(['Validation Message'], $event->getValidationMessages());
    }
}
