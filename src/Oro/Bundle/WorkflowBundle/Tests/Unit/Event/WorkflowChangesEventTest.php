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

    public function testGetPrevious()
    {
        $definition = new WorkflowDefinition();
        $previous = new WorkflowDefinition();

        $event = new WorkflowChangesEvent($definition, $previous);
        $this->assertSame($previous, $event->getPrevious());

        $event = new WorkflowChangesEvent($definition);

        $this->assertNull($event->getPrevious());
    }
}
