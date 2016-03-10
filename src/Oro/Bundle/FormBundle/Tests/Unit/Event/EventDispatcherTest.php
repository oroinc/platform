<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Event;

use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\FormBundle\Event\EventDispatcher;
use Symfony\Component\Form\FormInterface;

class EventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var EventDispatcher
     */
    protected $immutableDispatcher;

    protected function setUp()
    {
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->immutableDispatcher = new EventDispatcher($this->eventDispatcher);
    }

    public function testDispatchNonFormEvent()
    {
        $event = new Event();
        $eventName = 'test_event_name';
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($eventName, $event);

        $this->immutableDispatcher->dispatch($eventName, $event);
    }

    public function testDispatchFormEvent()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('form_name'));
        $data = [];

        $event = new FormProcessEvent($form, $data);
        $eventName = 'test_event_name';
        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch');
        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with($eventName, $event);
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with($eventName . '.form_name', $event);

        $this->immutableDispatcher->dispatch($eventName, $event);
    }
}
