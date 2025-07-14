<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Form\EventListener\DefaultValuesListener;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\ConfigExpression\ContextAccessor;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DefaultValuesListenerTest extends TestCase
{
    private ContextAccessor&MockObject $contextAccessor;
    private DefaultValuesListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);

        $this->listener = new DefaultValuesListener($this->contextAccessor);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = $this->listener->getSubscribedEvents();
        $this->assertCount(1, $events);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
    }

    public function testSetDefaultValues(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $defaultValues = ['test' => 'value'];

        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->with($workflowItem, 'value')
            ->willReturn('testValue');

        $data = $this->createMock(WorkflowData::class);
        $data->expects($this->once())
            ->method('set')
            ->with('test', 'testValue');
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $this->listener->initialize($workflowItem, $defaultValues);
        $this->listener->setDefaultValues($event);
    }
}
