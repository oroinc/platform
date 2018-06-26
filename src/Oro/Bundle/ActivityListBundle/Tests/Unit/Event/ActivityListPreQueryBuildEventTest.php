<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Event;

use Oro\Bundle\ActivityListBundle\Event\ActivityListPreQueryBuildEvent;

class ActivityListPreQueryBuildEventTest extends \PHPUnit\Framework\TestCase
{
    public function testEvent()
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
