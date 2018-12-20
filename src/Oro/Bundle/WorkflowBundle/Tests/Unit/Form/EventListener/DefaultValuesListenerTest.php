<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\WorkflowBundle\Form\EventListener\DefaultValuesListener;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\Form\FormEvents;

class DefaultValuesListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $workflowItem;

    /**
     * @var DefaultValuesListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMockBuilder('Oro\Component\ConfigExpression\ContextAccessor')
            ->disableOriginalConstructor()
            ->getMock();

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
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $defaultValues = array('test' => 'value');

        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->with($workflowItem, 'value')
            ->will($this->returnValue('testValue'));

        $data = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowData')
            ->disableOriginalConstructor()
            ->getMock();
        $data->expects($this->once())
            ->method('set')
            ->with('test', 'testValue');
        $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $event->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($data));

        $this->listener->initialize($workflowItem, $defaultValues);
        $this->listener->setDefaultValues($event);
    }
}
