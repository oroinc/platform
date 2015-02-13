<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\LayoutContext;

/**
 * This class contains unit tests related to CHANGE COUNTERS
 */
class DeferredLayoutManipulatorCountersTest extends DeferredLayoutManipulatorTestCase
{
    public function testAdd()
    {
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfAddedItems());

        $this->layoutManipulator->add('root', null, 'root');
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfAddedItems());

        $this->layoutManipulator->applyChanges(new LayoutContext(), true);
        $this->assertEquals(1, $this->layoutManipulator->getNumberOfAddedItems());

        $this->layoutManipulator->applyChanges(new LayoutContext(), true);
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfAddedItems());
    }

    public function testMove()
    {
        $this->layoutManipulator->add('root', null, 'root');
        $this->layoutManipulator->add('header', 'root', 'header');
        $this->layoutManipulator->add('logo', 'header', 'logo');

        $this->layoutManipulator->applyChanges(new LayoutContext(), true);
        $this->assertEquals(3, $this->layoutManipulator->getNumberOfAddedItems());

        $this->layoutManipulator->move('logo', 'root');
        $this->assertEquals(3, $this->layoutManipulator->getNumberOfAddedItems());

        $this->layoutManipulator->applyChanges(new LayoutContext(), true);
        $this->assertEquals(1, $this->layoutManipulator->getNumberOfAddedItems());

        $this->layoutManipulator->applyChanges(new LayoutContext(), true);
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfAddedItems());
    }
}
