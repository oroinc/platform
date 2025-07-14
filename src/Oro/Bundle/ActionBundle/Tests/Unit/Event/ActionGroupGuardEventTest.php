<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Event\ActionGroupGuardEvent;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupDefinition;
use PHPUnit\Framework\TestCase;

class ActionGroupGuardEventTest extends TestCase
{
    public function testEventMinimal(): void
    {
        $data = new ActionData();
        $definition = new ActionGroupDefinition();
        $event = new ActionGroupGuardEvent($data, $definition);

        $this->assertSame($data, $event->getActionData());
        $this->assertSame($definition, $event->getActionGroupDefinition());
        $this->assertNull($event->getErrors());
        $this->assertTrue($event->isAllowed());
    }

    public function testEventFull(): void
    {
        $errors = new ArrayCollection();
        $data = new ActionData();
        $definition = new ActionGroupDefinition();
        $event = new ActionGroupGuardEvent($data, $definition, $errors);
        $event->setAllowed(false);

        $this->assertSame($data, $event->getActionData());
        $this->assertSame($definition, $event->getActionGroupDefinition());
        $this->assertSame($errors, $event->getErrors());
        $this->assertFalse($event->isAllowed());
    }
}
