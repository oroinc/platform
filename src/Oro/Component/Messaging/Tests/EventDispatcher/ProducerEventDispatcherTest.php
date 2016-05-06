<?php
namespace Oro\Component\Messaging\Tests\EventDispatcher;

use Oro\Component\Messaging\EventDispatcher\MessageEvent;
use Oro\Component\Messaging\EventDispatcher\ProducerEventDispatcher;
use Oro\Component\Messaging\ZeroConfig\ZeroConfig;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProducerEventDispatcherTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithZeroConfigAsArgument()
    {
        new ProducerEventDispatcher($this->createZeroConfigMock());
    }

    public function testDispatchEmptyMessageIfEventIsNull()
    {
        $zeroConfig = $this->createZeroConfigMock();
        $zeroConfig
            ->expects($this->once())
            ->method('sendMessage')
            ->with('eventName', '""')
        ;

        $producer = new ProducerEventDispatcher($zeroConfig);
        $producer->dispatch('eventName');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid event instance. Expected instance of "Oro\Component\Messaging\EventDispatcher\MessageEvent"
     */
    public function testShouldThrowLogicExceptionIfEventIsUnsupportedType()
    {
        $producer = new ProducerEventDispatcher($this->createZeroConfigMock());
        $producer->dispatch('eventName', new Event());
    }

    public function testShouldDispatchEvent()
    {
        $zeroConfig = $this->createZeroConfigMock();
        $zeroConfig
            ->expects($this->once())
            ->method('sendMessage')
            ->with('eventName', '{"key":"value"}')
        ;

        $event = new MessageEvent([
            'key' => 'value',
        ]);

        $producer = new ProducerEventDispatcher($zeroConfig);
        $producer->dispatch('eventName', $event);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Method is not supported
     */
    public function testAddListenerShouldThrowMethodNotSupported()
    {
        $producer = new ProducerEventDispatcher($this->createZeroConfigMock());
        $producer->addListener('eventName', 'listener');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Method is not supported
     */
    public function testAddSubscriberShouldThrowMethodNotSupported()
    {
        $producer = new ProducerEventDispatcher($this->createZeroConfigMock());
        $producer->addSubscriber($this->createEventSubscriberMock());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Method is not supported
     */
    public function testRemoveListenerShouldThrowMethodNotSupported()
    {
        $producer = new ProducerEventDispatcher($this->createZeroConfigMock());
        $producer->removeListener('eventName', 'listener');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Method is not supported
     */
    public function testRemoveSubscriberShouldThrowMethodNotSupported()
    {
        $producer = new ProducerEventDispatcher($this->createZeroConfigMock());
        $producer->removeSubscriber($this->createEventSubscriberMock());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Method is not supported
     */
    public function testGetListenersShouldThrowMethodNotSupported()
    {
        $producer = new ProducerEventDispatcher($this->createZeroConfigMock());
        $producer->getListeners();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Method is not supported
     */
    public function testHasListenersShouldThrowMethodNotSupported()
    {
        $producer = new ProducerEventDispatcher($this->createZeroConfigMock());
        $producer->hasListeners($this->createEventSubscriberMock());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EventSubscriberInterface
     */
    protected function createEventSubscriberMock()
    {
        return $this->getMock('\Symfony\Component\EventDispatcher\EventSubscriberInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ZeroConfig
     */
    protected function createZeroConfigMock()
    {
        return $this->getMock('\Oro\Component\Messaging\ZeroConfig\ZeroConfig', [], [], '', false);
    }
}
