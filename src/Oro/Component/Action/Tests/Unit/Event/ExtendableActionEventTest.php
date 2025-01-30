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
        $context->expects($this->any())
            ->method('toArray')
            ->willReturn([]);
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
        $executionContext = new \stdClass();
        $dataArray = [
            'test' => 'value',
            ExtendableActionEvent::CONTEXT_KEY => $executionContext
        ];

        $storage = $this->createMock(AbstractStorage::class);
        $storage->expects($this->any())
            ->method('toArray')
            ->willReturn($dataArray);
        $context = $this->createMock(ActionDataStorageAwareInterface::class);
        $context->expects($this->once())
            ->method('getActionDataStorage')
            ->willReturn($storage);
        $event = new ExtendableActionEvent($context);

        $this->assertEquals(new ExtendableEventData(['test' => 'value']), $event->getData());
        $this->assertEquals($executionContext, $event->getContext());
    }

    public function testGetDataAbstractStorageContext()
    {
        $context = $this->createMock(AbstractStorage::class);
        $context->expects($this->any())
            ->method('toArray')
            ->willReturn(['test' => 'value']);
        $event = new ExtendableActionEvent($context);

        $this->assertEquals(new ExtendableEventData(['test' => 'value']), $event->getData());
    }

    public function testGetDataWhenDataSet()
    {
        $context = $this->createMock(AbstractStorage::class);
        $context->expects($this->any())
            ->method('toArray')
            ->willReturn([]);
        $data = new ExtendableEventData(['test' => 'value']);
        $event = new ExtendableActionEvent($context);
        $event->setData($data);

        $this->assertEquals($data, $event->getData());
    }
}
