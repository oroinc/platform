<?php

namespace Oro\Component\Action\Tests\Unit\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Action\Event\ExtendableEventData;
use Oro\Component\Action\Model\AbstractStorage;
use Oro\Component\Action\Model\ActionDataStorageAwareInterface;
use PHPUnit\Framework\TestCase;

class ExtendableConditionEventTest extends TestCase
{
    public function testGetContextWithNull(): void
    {
        $event = new ExtendableConditionEvent();
        $this->assertNull($event->getContext());
    }

    public function testGetContextWithNonNullValue(): void
    {
        $context = $this->createMock(AbstractStorage::class);
        $event = new ExtendableConditionEvent($context);

        $this->assertSame($context, $event->getContext());
    }

    public function testAddError(): void
    {
        $event = new ExtendableConditionEvent();
        $errorMessage = 'Error occurred';
        $errorContext = ['key' => 'value'];

        $event->addError($errorMessage, $errorContext);

        $this->assertTrue($event->hasErrors());

        $errors = $event->getErrors();
        $this->assertInstanceOf(ArrayCollection::class, $errors);
        $this->assertCount(1, $errors);

        $expectedError = ['message' => $errorMessage, 'context' => $errorContext];
        $this->assertSame($expectedError, $errors->first());
    }

    public function testHasErrors(): void
    {
        $event = new ExtendableConditionEvent();

        $this->assertFalse($event->hasErrors());

        $event->addError('Error message');
        $this->assertTrue($event->hasErrors());
    }

    public function testGetDataNullContext()
    {
        $event = new ExtendableConditionEvent(null);

        $this->assertNull($event->getData());
    }

    public function testGetDataArrayContext()
    {
        $context = ['test' => 'value'];
        $event = new ExtendableConditionEvent($context);

        $data = $event->getData();
        $this->assertInstanceOf(ExtendableEventData::class, $data);
        $this->assertSame($context, $data->toArray());
    }

    public function testGetDataActionDataStorageAwareContext()
    {
        $dataArray = ['test' => 'value'];

        $storage = $this->createMock(AbstractStorage::class);
        $storage->expects($this->any())
            ->method('toArray')
            ->willReturn($dataArray);
        $context = $this->createMock(ActionDataStorageAwareInterface::class);
        $context->expects($this->once())
            ->method('getActionDataStorage')
            ->willReturn($storage);
        $event = new ExtendableConditionEvent($context);

        $this->assertEquals(new ExtendableEventData($dataArray), $event->getData());
    }

    public function testGetDataAbstractStorageContext()
    {
        $dataArray = ['test' => 'value'];

        $context = $this->createMock(AbstractStorage::class);
        $context->expects($this->any())
            ->method('toArray')
            ->willReturn($dataArray);
        $event = new ExtendableConditionEvent($context);

        $this->assertEquals(new ExtendableEventData($dataArray), $event->getData());
    }

    public function testGetDataWhenDataSet()
    {
        $context = $this->createMock(AbstractStorage::class);
        $data = new ExtendableEventData(['test' => 'value']);
        $event = new ExtendableConditionEvent($context);
        $event->setData($data);

        $this->assertSame($data, $event->getData());
    }
}
