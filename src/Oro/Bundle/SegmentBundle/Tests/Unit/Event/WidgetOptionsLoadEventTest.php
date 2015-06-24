<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Event;

use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;

class WidgetOptionsLoadEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGettersAndSetters()
    {
        $originalOptions = [
            'oo' => 'ov',
        ];

        $options = [
            'option1' => 'val',
        ];

        $event = new WidgetOptionsLoadEvent($originalOptions);
        $this->assertEquals($originalOptions, $event->getWidgetOptions());

        $event->setWidgetOptions($options);
        $this->assertEquals($options, $event->getWidgetOptions());
    }
}
