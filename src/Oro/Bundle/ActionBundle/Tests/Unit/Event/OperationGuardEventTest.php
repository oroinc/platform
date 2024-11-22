<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Event\OperationGuardEvent;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use PHPUnit\Framework\TestCase;

class OperationGuardEventTest extends TestCase
{
    public function testEventMinimal()
    {
        $data = new ActionData();
        $definition = new OperationDefinition();
        $event = new OperationGuardEvent($data, $definition);

        $this->assertSame($data, $event->getActionData());
        $this->assertSame($definition, $event->getOperationDefinition());
        $this->assertNull($event->getErrors());
        $this->assertTrue($event->isAllowed());
    }

    public function testEventFull()
    {
        $errors = new ArrayCollection();
        $data = new ActionData();
        $definition = new OperationDefinition();
        $event = new OperationGuardEvent($data, $definition, $errors);
        $event->setAllowed(false);

        $this->assertSame($data, $event->getActionData());
        $this->assertSame($definition, $event->getOperationDefinition());
        $this->assertSame($errors, $event->getErrors());
        $this->assertFalse($event->isAllowed());
    }
}
