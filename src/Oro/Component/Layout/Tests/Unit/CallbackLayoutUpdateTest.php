<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\CallbackLayoutUpdate;
use Oro\Component\Layout\LayoutManipulatorInterface;

class CallbackLayoutUpdateTest extends \PHPUnit_Framework_TestCase
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

        $layoutManipulator = $this->getMock('Oro\Component\Layout\LayoutManipulatorInterface');
        $layoutManipulator->expects($this->once())
            ->method('add')
            ->with('id', 'parentId', 'blockType');

        $layoutUpdate->updateLayout($layoutManipulator);
    }

    /**
     * @param LayoutManipulatorInterface $layoutManipulator
     */
    public function callbackFunction(LayoutManipulatorInterface $layoutManipulator)
    {
        $layoutManipulator->add('id', 'parentId', 'blockType');
    }
}
