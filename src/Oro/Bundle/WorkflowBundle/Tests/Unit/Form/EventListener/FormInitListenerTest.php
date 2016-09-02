<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormEvents;

use Oro\Bundle\WorkflowBundle\Form\EventListener\FormInitListener;

class FormInitListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormInitListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->listener = new FormInitListener();
    }

    public function testGetSubscribedEvents()
    {
        $events = $this->listener->getSubscribedEvents();
        $this->assertCount(1, $events);
        $this->assertArrayHasKey(FormEvents::PRE_SET_DATA, $events);
    }

    public function testExecuteInitAction()
    {
        $workflowItem = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Entity\WorkflowItem')
            ->disableOriginalConstructor()
            ->getMock();
        $action = $this->getMockBuilder('Oro\Component\Action\Action\ActionInterface')
            ->getMock();
        $action->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $this->listener->initialize($workflowItem, $action);
        $this->listener->executeInitAction();
    }
}
