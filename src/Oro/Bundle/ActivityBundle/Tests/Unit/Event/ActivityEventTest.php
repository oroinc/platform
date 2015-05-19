<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Event;

use Oro\Bundle\ActivityBundle\Event\ActivityEvent;

class ActivityEventTest extends \PHPUnit_Framework_TestCase
{
    public function testEvent()
    {
        $target   = new \stdClass();
        $activity = new \stdClass();

        $event = new ActivityEvent($activity, $target);

        $this->assertSame($target, $event->getTarget());
        $this->assertSame($activity, $event->getActivity());
    }
}
