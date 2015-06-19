<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Event;

use Oro\Bundle\ActivityListBundle\Event\ActivityConditionOptionsLoadEvent;

class ActivityConditionOptionsLoadEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $originalOptions = [
            'a' => 'b',
        ];

        $modifiedOptions = [
            'c' => 'd',
        ];

        $event = new ActivityConditionOptionsLoadEvent($originalOptions);
        $this->assertEquals($originalOptions, $event->getOptions());

        $event->setOptions($modifiedOptions);
        $this->assertEquals($modifiedOptions, $event->getOptions());
    }
}
