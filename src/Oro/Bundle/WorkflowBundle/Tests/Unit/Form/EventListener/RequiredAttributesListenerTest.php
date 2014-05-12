<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\WorkflowBundle\Form\EventListener\RequiredAttributesListener;
use Symfony\Component\Form\FormEvents;

class RequiredAttributesListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequiredAttributesListener
     */
    protected $listener;

    protected function setUp()
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
        $data = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowData')
            ->disableOriginalConstructor()
            ->getMock();
        $data->expects($this->never())
            ->method($this->anything());


        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));
        $event->expects($this->never())
            ->method('setData');

        $this->listener->onSubmit($event);
    }

    public function testOnPreSetDataOnlyWorkflowData()
    {
        $data = new \stdClass();

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));
        $event->expects($this->never())
            ->method('setData');

        $this->listener->onPreSetData($event);
    }

    public function testEvents()
    {
        $attributeNames = array('test');
        $values = array('test' => 'value');

        $data = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowData')
            ->disableOriginalConstructor()
            ->getMock();
        $data->expects($this->once())
            ->method('getValues')
            ->with($attributeNames)
            ->will($this->returnValue($values));

        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));
        $event->expects($this->once())
            ->method('setData')
            ->with($this->isInstanceOf('Oro\Bundle\WorkflowBundle\Model\WorkflowData'));

        $this->listener->initialize($attributeNames);
        $this->listener->onPreSetData($event);

        // Test submit data
        $formData = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowData')
            ->disableOriginalConstructor()
            ->getMock();
        $formData->expects($this->once())
            ->method('getValues')
            ->will($this->returnValue($values));
        $data->expects($this->once())
            ->method('add')
            ->with($values);

        $submitEvent = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $submitEvent->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($formData));
        $submitEvent->expects($this->once())
            ->method('setData')
            ->with($this->isInstanceOf('Oro\Bundle\WorkflowBundle\Model\WorkflowData'));

        $this->listener->onSubmit($submitEvent);
    }
}
