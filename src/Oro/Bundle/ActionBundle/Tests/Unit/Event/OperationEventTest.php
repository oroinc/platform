<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Event\OperationEvent;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use PHPUnit\Framework\TestCase;

class OperationEventTest extends TestCase
{
    public function testEventMinimal()
    {
        $data = new ActionData();
        $definition = new OperationDefinition();
        $event = $this->getMockForAbstractClass(OperationEvent::class, [$data, $definition]);

        $this->assertSame($data, $event->getActionData());
        $this->assertSame($definition, $event->getOperationDefinition());
        $this->assertNull($event->getErrors());
    }

    public function testEventFull()
    {
        $errors = new ArrayCollection();
        $data = new ActionData();
        $definition = new OperationDefinition();
        $event = $this->getMockForAbstractClass(OperationEvent::class, [$data, $definition, $errors]);

        $this->assertSame($data, $event->getActionData());
        $this->assertSame($definition, $event->getOperationDefinition());
        $this->assertSame($errors, $event->getErrors());
    }
}
