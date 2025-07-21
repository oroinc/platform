<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;
use PHPUnit\Framework\TestCase;

class WorkflowChangesEventTest extends TestCase
{
    public function testGetDefinition(): void
    {
        $definition = new WorkflowDefinition();

        $event = new WorkflowChangesEvent($definition);

        $this->assertSame($definition, $event->getDefinition());
    }

    public function testGetOriginalDefinition(): void
    {
        $definition = new WorkflowDefinition();
        $original = new WorkflowDefinition();

        $event = new WorkflowChangesEvent($definition, $original);
        $this->assertSame($original, $event->getOriginalDefinition());

        $event = new WorkflowChangesEvent($definition);

        $this->assertNull($event->getOriginalDefinition());
    }
}
