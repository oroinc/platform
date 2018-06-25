<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\CallbackLayoutUpdate;
use Oro\Component\Layout\LayoutItemInterface;
use Oro\Component\Layout\LayoutManipulatorInterface;

class CallbackLayoutUpdateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @expectedException \Oro\Component\Layout\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "callable", "integer" given.
     */
    public function testInvalidCallbackType()
    {
        new CallbackLayoutUpdate(123);
    }

    public function testCallbackCall()
    {
        $layoutUpdate = new CallbackLayoutUpdate([$this, 'callbackFunction']);

        $layoutManipulator = $this->createMock('Oro\Component\Layout\LayoutManipulatorInterface');
        $item              = $this->createMock('Oro\Component\Layout\LayoutItemInterface');

        $layoutManipulator->expects($this->once())
            ->method('add')
            ->with('id', 'parentId', 'blockType');
        $item->expects($this->once())
            ->method('getId')
            ->will($this->returnValue('parentId'));
        $item->expects($this->once())
            ->method('getTypeName')
            ->will($this->returnValue('blockType'));

        $layoutUpdate->updateLayout($layoutManipulator, $item);
    }

    /**
     * @param LayoutManipulatorInterface $layoutManipulator
     * @param LayoutItemInterface        $item
     */
    public function callbackFunction(LayoutManipulatorInterface $layoutManipulator, LayoutItemInterface $item)
    {
        $layoutManipulator->add('id', $item->getId(), $item->getTypeName());
    }
}
