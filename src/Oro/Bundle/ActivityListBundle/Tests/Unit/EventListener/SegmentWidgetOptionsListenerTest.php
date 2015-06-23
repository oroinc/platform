<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\EventListener;

use Oro\Bundle\ActivityListBundle\EventListener\SegmentWidgetOptionsListener;
use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;

class SegmentWidgetOptionsListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testListener()
    {
        $options = [
            'filters'    => [],
            'column'     => [],
            'extensions' => [],
        ];

        $expectedOptions = [
            'filters'    => [],
            'column'     => [],
            'extensions' => [
                'oroactivitylist/js/app/components/segment-component-extension',
            ],
        ];

        $listener = new SegmentWidgetOptionsListener();
        $event = new WidgetOptionsLoadEvent($options);
        $listener->onLoad($event);
        $this->assertEquals($expectedOptions, $event->getWidgetOptions());
    }
}
