<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Event;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\EventDispatcher;
use Oro\Bundle\WorkflowBundle\Event\WorkflowItemAwareEvent;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class EventDispatcherTest extends TestCase
{
    private EventDispatcherInterface $innerDispatcher;
    private EventDispatcher $dispatcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->innerDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->dispatcher = new EventDispatcher($this->innerDispatcher);
    }

    public function testDispatch(): void
    {
        $eventName = 'some_event';
        $workflowName = 'workflow_name';
        $contextName = 'context';

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getWorkflowName')->willReturn($workflowName);

        $event = $this->createConfiguredMock(
            WorkflowItemAwareEvent::class,
            [
                'getWorkflowItem' => $workflowItem,
                'getName' => $eventName
            ]
        );

        $this->innerDispatcher
            ->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [$event, 'oro_workflow.some_event'],
                [$event, 'oro_workflow.workflow_name.some_event'],
                [$event, 'oro_workflow.workflow_name.some_event.context']
            );

        $this->dispatcher->dispatch($event, $contextName);
    }
}
