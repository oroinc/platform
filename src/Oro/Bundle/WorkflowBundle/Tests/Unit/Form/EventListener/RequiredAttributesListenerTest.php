<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\WorkflowBundle\Form\EventListener\RequiredAttributesListener;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class RequiredAttributesListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequiredAttributesListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new RequiredAttributesListener();
    }

    public function testGetSubscribedEvents()
    {
        $events = $this->listener->getSubscribedEvents();
        $this->assertCount(2, $events);

        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
        $this->assertArrayHasKey(FormEvents::SUBMIT, $events);
    }

    public function testOnSubmitNoWorkflowData()
    {
        $data = $this->createMock(WorkflowData::class);
        $data->expects($this->never())
            ->method($this->anything());

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $event->expects($this->never())
            ->method('setData');

        $this->listener->onSubmit($event);
    }

    public function testOnPreSetDataOnlyWorkflowData()
    {
        $data = new \stdClass();

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $event->expects($this->never())
            ->method('setData');

        $this->listener->onPreSetData($event);
    }

    public function testEvents()
    {
        $attributeNames = ['test'];
        $values = ['test' => 'value'];

        $data = $this->createMock(WorkflowData::class);
        $data->expects($this->once())
            ->method('getValues')
            ->with($attributeNames)
            ->willReturn($values);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($data);
        $event->expects($this->once())
            ->method('setData')
            ->with($this->isInstanceOf(WorkflowData::class));

        $this->listener->initialize($attributeNames);
        $this->listener->onPreSetData($event);

        // Test submit data
        $formData = $this->createMock(WorkflowData::class);
        $formData->expects($this->once())
            ->method('getValues')
            ->willReturn($values);
        $data->expects($this->once())
            ->method('add')
            ->with($values);

        $submitEvent = $this->createMock(FormEvent::class);
        $submitEvent->expects($this->once())
            ->method('getData')
            ->willReturn($formData);
        $submitEvent->expects($this->once())
            ->method('setData')
            ->with($this->isInstanceOf(WorkflowData::class));

        $this->listener->onSubmit($submitEvent);
    }
}
