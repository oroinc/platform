<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\CallbackLayoutUpdate;
use Oro\Component\Layout\Exception\UnexpectedTypeException;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;

class CallbackLayoutUpdateTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidCallbackType()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "callable", "integer" given.');

        new CallbackLayoutUpdate(123);
    }

    public function testCallbackCall()
    {
        $layoutUpdate = new CallbackLayoutUpdate([$this, 'callbackFunction']);

        $layoutManipulator = $this->createMock(LayoutManipulatorInterface::class);
        $item = $this->createMock(LayoutItemInterface::class);

        $layoutManipulator->expects($this->once())
            ->method('add')
            ->with('id', 'parentId', 'blockType');
        $item->expects($this->once())
            ->method('getId')
            ->willReturn('parentId');
        $item->expects($this->once())
            ->method('getTypeName')
            ->willReturn('blockType');

        $layoutUpdate->updateLayout($layoutManipulator, $item);
    }

    public function callbackFunction(LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item)
    {
        $layoutManipulator->add('id', $item->getId(), $item->getTypeName());
    }
}
