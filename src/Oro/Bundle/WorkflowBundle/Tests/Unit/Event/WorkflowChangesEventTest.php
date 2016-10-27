<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Event\WorkflowChangesEvent;

class WorkflowChangesEventTest extends \PHPUnit_Framework_TestCase
{
    public function testGetDefinition()
    {
        $definition = new WorkflowDefinition();

        $event = new WorkflowChangesEvent($definition);

        $this->assertSame($definition, $event->getDefinition());
    }

    public function testGetOriginalDefinition()
    {
        $definition = new WorkflowDefinition();
        $original = new WorkflowDefinition();

        $event = new WorkflowChangesEvent($definition, $original);
        $this->assertSame($original, $event->getOriginalDefinition());

        $event = new WorkflowChangesEvent($definition);

        $this->assertNull($event->getOriginalDefinition());
    }
}
