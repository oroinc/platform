<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Event\ActionGroupEvent;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use PHPUnit\Framework\TestCase;

class ActionGroupEventTest extends TestCase
{
    public function testEventMinimal(): void
    {
        $data = new ActionData();
        $definition = new ActionGroupDefinition();
        $event = $this->getMockForAbstractClass(ActionGroupEvent::class, [$data, $definition]);

        $this->assertSame($data, $event->getActionData());
        $this->assertSame($definition, $event->getActionGroupDefinition());
        $this->assertNull($event->getErrors());
    }

    public function testEventFull(): void
    {
        $errors = new ArrayCollection();
        $data = new ActionData();
        $definition = new ActionGroupDefinition();
        $event = $this->getMockForAbstractClass(ActionGroupEvent::class, [$data, $definition, $errors]);

        $this->assertSame($data, $event->getActionData());
        $this->assertSame($definition, $event->getActionGroupDefinition());
        $this->assertSame($errors, $event->getErrors());
    }
}
