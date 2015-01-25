<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\DeferredLayoutManipulatorWithChangeCounter;

class DeferredLayoutManipulatorWithChangeCounterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $baseLayoutManipulator;

    /** @var DeferredLayoutManipulatorWithChangeCounter */
    protected $layoutManipulator;

    protected function setUp()
    {
        $this->baseLayoutManipulator = $this->getMock('Oro\Component\Layout\DeferredLayoutManipulatorInterface');
        $this->layoutManipulator     = new DeferredLayoutManipulatorWithChangeCounter($this->baseLayoutManipulator);
    }

    public function testAdd()
    {
        $id        = 'test_id';
        $parentId  = 'test_parent_id';
        $blockType = 'test_block_type';
        $options   = ['test' => 123];

        $this->baseLayoutManipulator->expects($this->once())
            ->method('add')
            ->with($id, $parentId, $blockType, $options);

        $this->assertEquals(0, $this->layoutManipulator->getNumberOfAddedItems());
        $this->assertSame(
            $this->layoutManipulator,
            $this->layoutManipulator->add($id, $parentId, $blockType, $options)
        );
        $this->assertEquals(1, $this->layoutManipulator->getNumberOfAddedItems());
        $this->layoutManipulator->resetCounters();
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfAddedItems());
    }

    public function testRemove()
    {
        $id = 'test_id';

        $this->baseLayoutManipulator->expects($this->once())
            ->method('remove')
            ->with($id);

        $this->assertEquals(0, $this->layoutManipulator->getNumberOfRemovedItems());
        $this->assertSame(
            $this->layoutManipulator,
            $this->layoutManipulator->remove($id)
        );
        $this->assertEquals(1, $this->layoutManipulator->getNumberOfRemovedItems());
        $this->layoutManipulator->resetCounters();
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfRemovedItems());
    }

    public function testMove()
    {
        $id        = 'test_id';
        $parentId  = 'test_parent_id';
        $siblingId = 'test_sibling_id';
        $prepend   = true;

        $this->baseLayoutManipulator->expects($this->once())
            ->method('move')
            ->with($id, $parentId, $siblingId, $prepend);

        $this->assertEquals(0, $this->layoutManipulator->getNumberOfAddedItems());
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfRemovedItems());
        $this->assertSame(
            $this->layoutManipulator,
            $this->layoutManipulator->move($id, $parentId, $siblingId, $prepend)
        );
        $this->assertEquals(1, $this->layoutManipulator->getNumberOfAddedItems());
        $this->assertEquals(1, $this->layoutManipulator->getNumberOfRemovedItems());
        $this->layoutManipulator->resetCounters();
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfAddedItems());
        $this->assertEquals(0, $this->layoutManipulator->getNumberOfRemovedItems());
    }

    public function testAddAlias()
    {
        $alias = 'test_alias';
        $id    = 'test_id';

        $this->baseLayoutManipulator->expects($this->once())
            ->method('addAlias')
            ->with($alias, $id);

        $this->assertSame(
            $this->layoutManipulator,
            $this->layoutManipulator->addAlias($alias, $id)
        );
    }

    public function testRemoveAlias()
    {
        $alias = 'test_alias';

        $this->baseLayoutManipulator->expects($this->once())
            ->method('removeAlias')
            ->with($alias);

        $this->assertSame(
            $this->layoutManipulator,
            $this->layoutManipulator->removeAlias($alias)
        );
    }

    public function testApplyChanges()
    {
        $this->baseLayoutManipulator->expects($this->once())
            ->method('applyChanges');

        $this->layoutManipulator->applyChanges();
    }
}
