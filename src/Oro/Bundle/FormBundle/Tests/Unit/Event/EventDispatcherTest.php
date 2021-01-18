<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Event;

use Oro\Bundle\FormBundle\Event\EventDispatcher;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\EventDispatcher\Event;

class EventDispatcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var EventDispatcher
     */
    protected $immutableDispatcher;

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->immutableDispatcher = new EventDispatcher($this->eventDispatcher);
    }

    public function testDispatchNonFormEvent()
    {
        $event = new Event();
        $eventName = 'test_event_name';
        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with($event, $eventName);

        $this->immutableDispatcher->dispatch($event, $eventName);
    }

    public function testDispatchFormEvent()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface $form */
        $form = $this->createMock('Symfony\Component\Form\FormInterface');
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
            ->with($event, $eventName);
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with($event, $eventName . '.form_name');

        $this->immutableDispatcher->dispatch($event, $eventName);
    }
}
