<?php

namespace Oro\Bundle\ActivityBundle\Tests\Unit\Event;

use Oro\Bundle\ActivityBundle\Event\ActivityEvent;
use PHPUnit\Framework\TestCase;

class ActivityEventTest extends TestCase
{
    public function testEvent(): void
    {
        $target = new \stdClass();
        $activity = new \stdClass();

        $event = new ActivityEvent($activity, $target);

        $this->assertSame($target, $event->getTarget());
        $this->assertSame($activity, $event->getActivity());
    }
}
