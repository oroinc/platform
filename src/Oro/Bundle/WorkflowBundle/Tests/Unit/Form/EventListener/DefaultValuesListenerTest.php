<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Form\EventListener\DefaultValuesListener;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DefaultValuesListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var DefaultValuesListener */
    private $listener;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);

        $this->listener = new DefaultValuesListener($this->contextAccessor);
    }

    public function testGetSubscribedEvents()
    {
        $events = $this->listener->getSubscribedEvents();
        $this->assertCount(1, $events);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
    }

    public function testSetDefaultValues()
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
