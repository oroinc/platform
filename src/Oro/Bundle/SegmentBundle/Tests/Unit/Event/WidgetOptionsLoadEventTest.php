<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Event;

use Oro\Bundle\SegmentBundle\Event\WidgetOptionsLoadEvent;
use PHPUnit\Framework\TestCase;

class WidgetOptionsLoadEventTest extends TestCase
{
    public function testGettersAndSetters(): void
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
