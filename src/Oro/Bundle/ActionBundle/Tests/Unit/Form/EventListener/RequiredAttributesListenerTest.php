<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\ActionBundle\Form\EventListener\RequiredAttributesListener;
use Oro\Bundle\ActionBundle\Model\ActionData;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class RequiredAttributesListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var RequiredAttributesListener */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new RequiredAttributesListener();
    }

    protected function tearDown()
    {
        unset($this->listener);
    }

    public function testGetSubscribedEvents()
    {
        $events = $this->listener->getSubscribedEvents();

        $this->assertCount(2, $events);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
        $this->assertEquals('onPreSetData', $events[FormEvents::PRE_SET_DATA]);
        $this->assertArrayHasKey(FormEvents::SUBMIT, $events);
        $this->assertEquals('onSubmit', $events[FormEvents::SUBMIT]);
    }

    public function testEvents()
    {
        $attributeNames = ['test'];
        $values = ['test' => 'value'];

        $data = $this->createActionData();

        $event = $this->createFormEvent();
        $event->expects($this->once())->method('getData')->willReturn($data);

        $this->listener->initialize($attributeNames);
        $this->listener->onPreSetData($event);

        // Test submit data
        $formData = $this->createActionData();
        $formData->expects($this->once())->method('getValues')->willReturn($values);

        $data->expects($this->once())->method('__set')->with('test', 'value');

        $submitEvent = $this->createFormEvent();
        $submitEvent->expects($this->once())->method('getData')->willReturn($formData);

        $this->listener->onSubmit($submitEvent);
    }

    public function testOnSubmitNoActionData()
    {
        $data = $this->createActionData();
        $data->expects($this->never())->method($this->anything());

        $event = $this->createFormEvent();
        $event->expects($this->once())->method('getData')->willReturn($data);
        $event->expects($this->never())->method('setData');

        $this->listener->onSubmit($event);
    }

    public function testOnPreSetDataNoActionData()
    {
        $event = $this->createFormEvent();
        $event->expects($this->once())->method('getData')->will($this->returnValue(new \stdClass()));
        $event->expects($this->never())->method('setData');

        $this->listener->onPreSetData($event);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ActionData
     */
    protected function createActionData()
    {
        return $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ActionData')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|FormEvent
     */
    protected function createFormEvent()
    {
        return $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
