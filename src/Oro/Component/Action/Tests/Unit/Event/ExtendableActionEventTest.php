<?php

namespace Oro\Component\Action\Tests\Unit\Event;

use Oro\Component\Action\Event\ExtendableActionEvent;
use Oro\Component\Action\Event\ExtendableEventData;
use Oro\Component\Action\Model\AbstractStorage;
use Oro\Component\Action\Model\ActionDataStorageAwareInterface;
use PHPUnit\Framework\TestCase;

class ExtendableActionEventTest extends TestCase
{
    public function testGetContextWithNull(): void
    {
        $event = new ExtendableActionEvent();

        $this->assertNull($event->getContext());
    }

    public function testGetContextWithNonNullValue(): void
    {
        $context = $this->createMock(AbstractStorage::class);
        $event = new ExtendableActionEvent($context);

        $this->assertSame($context, $event->getContext());
    }

    public function testGetDataNullContext()
    {
        $event = new ExtendableActionEvent(null);

        $this->assertNull($event->getData());
    }

    public function testGetDataArrayContext()
    {
        $context = ['test' => 'value'];
        $event = new ExtendableActionEvent($context);

        $data = $event->getData();
        $this->assertInstanceOf(ExtendableEventData::class, $data);
        $this->assertSame($context, $data->toArray());
    }

    public function testGetDataActionDataStorageAwareContext()
    {
        $storage = $this->createMock(AbstractStorage::class);
        $context = $this->createMock(ActionDataStorageAwareInterface::class);
        $context->expects($this->once())
            ->method('getActionDataStorage')
            ->willReturn($storage);
        $event = new ExtendableActionEvent($context);

        $this->assertSame($storage, $event->getData());
    }

    public function testGetDataAbstractStorageContext()
    {
        $context = $this->createMock(AbstractStorage::class);
        $event = new ExtendableActionEvent($context);

        $this->assertSame($context, $event->getData());
    }

    public function testGetDataWhenDataSet()
    {
        $context = $this->createMock(AbstractStorage::class);
        $data = new ExtendableEventData(['test' => 'value']);
        $event = new ExtendableActionEvent($context);
        $event->setData($data);

        $this->assertSame($data, $event->getData());
    }
}
