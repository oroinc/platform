<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ActionBundle\Form\EventListener\RequiredAttributesListener;
use Oro\Bundle\ActionBundle\Model\ActionData;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class RequiredAttributesListenerTest extends TestCase
{
    private RequiredAttributesListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new RequiredAttributesListener();
    }

    public function testGetSubscribedEvents(): void
    {
        $events = $this->listener->getSubscribedEvents();

        $this->assertCount(2, $events);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
        $this->assertEquals('onPreSetData', $events[FormEvents::PRE_SET_DATA]);
        $this->assertArrayHasKey(FormEvents::SUBMIT, $events);
        $this->assertEquals('onSubmit', $events[FormEvents::SUBMIT]);
    }

    public function testEvents(): void
    {
        $attributeNames = ['test'];
        $values = ['test' => 'value'];

        $data = $this->createMock(ActionData::class);

        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $this->listener->initialize($attributeNames);
        $this->listener->onPreSetData($event);

        // Test submit data
        $formData = $this->createMock(ActionData::class);
        $formData->expects($this->once())
            ->method('getValues')
            ->willReturn($values);

        $data->expects($this->once())
            ->method('__set')
            ->with('test', 'value');

        $submitEvent = $this->createMock(FormEvent::class);
        $submitEvent->expects($this->once())
            ->method('getData')
            ->willReturn($formData);

        $this->listener->onSubmit($submitEvent);
    }

    public function testOnSubmitNoActionData(): void
    {
        $data = $this->createMock(ActionData::class);
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

    public function testOnPreSetDataNoActionData(): void
    {
        $event = $this->createMock(FormEvent::class);
        $event->expects($this->once())
            ->method('getData')
            ->willReturn(new \stdClass());
        $event->expects($this->never())
            ->method('setData');

        $this->listener->onPreSetData($event);
    }
}
