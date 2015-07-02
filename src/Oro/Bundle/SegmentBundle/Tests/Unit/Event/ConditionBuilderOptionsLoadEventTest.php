<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Event;

use Oro\Bundle\SegmentBundle\Event\ConditionBuilderOptionsLoadEvent;

class ConditionBuilderOptionsLoadEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersAndSetters()
    {
        $originalOptions = [
            'oo' => 'ov',
        ];

        $options = [
            'option1' => 'val',
        ];

        $event = new ConditionBuilderOptionsLoadEvent($originalOptions);
        $this->assertEquals($originalOptions, $event->getOptions());

        $event->setOptions($options);
        $this->assertEquals($options, $event->getOptions());
    }
}
