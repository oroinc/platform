<?php

namespace Oro\Component\Layout\Tests\Unit;

/**
 * This class contains unit tests related to CHANGE COUNTERS
 */
class DeferredLayoutManipulatorCountersTest extends DeferredLayoutManipulatorTestCase
{
    public function testAdd()
    {
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfAddedItems());

        $this->layoutManipulator->add('root', null, 'root');
        $this->assertEquals(1, $this->layoutManipulator->getNumberOfAddedItems());

        $this->layoutManipulator->resetCounters();
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfAddedItems());
    }

    public function testRemove()
    {
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfRemovedItems());

        $this->layoutManipulator->remove('root');
        $this->assertEquals(1, $this->layoutManipulator->getNumberOfRemovedItems());

        $this->layoutManipulator->resetCounters();
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfRemovedItems());
    }

    public function testMove()
    {
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfAddedItems());
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfRemovedItems());

        $this->layoutManipulator->move('logo', 'root');
        $this->assertEquals(1, $this->layoutManipulator->getNumberOfAddedItems());
        $this->assertEquals(1, $this->layoutManipulator->getNumberOfRemovedItems());

        $this->layoutManipulator->resetCounters();
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfAddedItems());
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfRemovedItems());
    }
}
