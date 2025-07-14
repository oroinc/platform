<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Event;

use Oro\Bundle\ActivityListBundle\Event\ActivityConditionOptionsLoadEvent;
use PHPUnit\Framework\TestCase;

class ActivityConditionOptionsLoadEventTest extends TestCase
{
    public function testEvent(): void
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
