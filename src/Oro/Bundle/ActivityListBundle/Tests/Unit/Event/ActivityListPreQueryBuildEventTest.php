<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Event;

use Oro\Bundle\ActivityListBundle\Event\ActivityListPreQueryBuildEvent;
use PHPUnit\Framework\TestCase;

class ActivityListPreQueryBuildEventTest extends TestCase
{
    public function testEvent(): void
    {
        $targetClass = 'testClass';
        $targetId = 1;
        $targetIds = [2, 3];

        $event = new ActivityListPreQueryBuildEvent($targetClass, $targetId);
        $this->assertEquals($targetId, $event->getTargetId());
        $this->assertEquals([$targetId], $event->getTargetIds());
        $this->assertEquals($targetClass, $event->getTargetClass());

        $event = new ActivityListPreQueryBuildEvent($targetClass, $targetId);
        $event->setTargetIds($targetIds);
        $this->assertEquals($targetIds, $event->getTargetIds());
    }
}
