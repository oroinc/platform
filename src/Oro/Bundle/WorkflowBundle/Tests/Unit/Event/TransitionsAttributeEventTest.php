<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\WorkflowBundle\Event\TransitionsAttributeEvent;
use Oro\Bundle\ActionBundle\Model\Attribute;

class TransitionsAttributeEventTest extends \PHPUnit_Framework_TestCase
{
    public function testAttributeEvent()
    {
        $attribute = new Attribute();
        $attribute
            ->setName('name_test')
            ->setType('type_test');
        $attributeOpt = ['test1', 'test2'];
        $attributeOptChanged = ['test1', 'test2', 'test3'];
        $options = ['option1', 'option2'];
        $event = new TransitionsAttributeEvent($attribute, $attributeOpt, $options);

        $this->assertEquals($attribute, $event->getAttribute());
        $this->assertEquals($attributeOpt, $event->getAttributeOptions());
        $this->assertEquals($options, $event->getOptions());

        $event->setAttributeOptions($attributeOptChanged);
        $this->assertEquals($attributeOptChanged, $event->getAttributeOptions());
    }
}
