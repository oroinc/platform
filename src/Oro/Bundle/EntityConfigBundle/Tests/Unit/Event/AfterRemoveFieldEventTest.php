<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Event;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\AfterRemoveFieldEvent;

class AfterRemoveFieldEventTest extends \PHPUnit_Framework_TestCase
{
    public function testAfterRemoveFieldEvent()
    {
        $fieldConfigModel = new FieldConfigModel('color');
        $event = new AfterRemoveFieldEvent($fieldConfigModel);

        $this->assertSame($fieldConfigModel, $event->getFieldConfig());
    }
}
