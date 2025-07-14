<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Event\ActionDataAwareEvent;
use Oro\Bundle\ActionBundle\Model\ActionData;
use PHPUnit\Framework\TestCase;

class ActionDataAwareEventTest extends TestCase
{
    public function testEventMinimal(): void
    {
        $data = new ActionData();
        $event = $this->getMockForAbstractClass(ActionDataAwareEvent::class, [$data]);

        $this->assertSame($data, $event->getActionData());
        $this->assertNull($event->getErrors());
    }

    public function testEventFull(): void
    {
        $data = new ActionData();
        $errors = new ArrayCollection();
        $event = $this->getMockForAbstractClass(ActionDataAwareEvent::class, [$data, $errors]);

        $this->assertSame($data, $event->getActionData());
        $this->assertSame($errors, $event->getErrors());
    }
}
