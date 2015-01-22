<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\HierarchyCollection;

class HierarchyCollectionTest extends \PHPUnit_Framework_TestCase
{
    /** @var HierarchyCollection */
    protected $hierarchyCollection;

    protected function setUp()
    {
        $this->hierarchyCollection = new HierarchyCollection();
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->hierarchyCollection->isEmpty());

        $this->hierarchyCollection->add([], 'root');
        $this->assertFalse($this->hierarchyCollection->isEmpty());

        $this->hierarchyCollection->remove(['root']);
        $this->assertTrue($this->hierarchyCollection->isEmpty());
    }

    public function testGetRootId()
    {
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item');

        $this->assertEquals('root', $this->hierarchyCollection->getRootId());
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage The root item does not exist.
     */
    public function testGetRootIdForEmptyHierarchy()
    {
        $this->hierarchyCollection->getRootId();
    }

    public function testGet()
    {
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root', 'item1'], 'item2');

        $this->assertSame(
            [
                'root' => [
                    'item1' => [
                        'item2' => []
                    ]
                ]
            ],
            $this->hierarchyCollection->get([])
        );
        $this->assertSame(
            [
                'item1' => [
                    'item2' => []
                ]
            ],
            $this->hierarchyCollection->get(['root'])
        );
        $this->assertSame(
            [
                'item2' => []
            ],
            $this->hierarchyCollection->get(['root', 'item1'])
        );
        $this->assertSame(
            [],
            $this->hierarchyCollection->get(['root', 'item1', 'item2'])
        );

        // test for unknown paths
        $this->assertSame(
            [],
            $this->hierarchyCollection->get(['unknown'])
        );
        $this->assertSame(
            [],
            $this->hierarchyCollection->get(['unknown1', 'unknown2'])
        );
        $this->assertSame(
            [],
            $this->hierarchyCollection->get(['root', 'item1', 'unknown'])
        );
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot add "item1" item to "root" because "root" root item does not exist.
     */
    public function testAddToFirstHierarchyLevelWithoutRoot()
    {
        $this->hierarchyCollection->add(['root'], 'item1');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot add "item3" item to "root/item1/item2" because "root" root item does not exist.
     */
    public function testAddWithoutRoot()
    {
        $this->hierarchyCollection->add(['root', 'item1', 'item2'], 'item3');
    }

    // @codingStandardsIgnoreStart
    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot add "item3" item to "root/unknown/item2" because "root" item does not have "unknown" child.
     */
    // @codingStandardsIgnoreEnd
    public function testAddToUnknown()
    {
        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root', 'item1'], 'item2');

        // do test
        $this->hierarchyCollection->add(['root', 'unknown', 'item2'], 'item3');
    }

    /**
     * @expectedException \Oro\Component\Layout\Exception\LogicException
     * @expectedExceptionMessage Cannot add "item1" item to "root" because such item already exists.
     */
    public function testAddDuplicate()
    {
        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root', 'item1'], 'item2');

        // do test
        $this->hierarchyCollection->add(['root'], 'item1');
    }

    public function testRemove()
    {
        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root', 'item1'], 'item2');
        $this->hierarchyCollection->add(['root', 'item1'], 'item3');
        $this->hierarchyCollection->add(['root', 'item1'], 'item4');

        // do test
        $this->hierarchyCollection->remove(['root', 'item1', 'item3']);
        $this->assertSame(
            [
                'root' => [
                    'item1' => [
                        'item2' => [],
                        'item4' => []
                    ]
                ]
            ],
            $this->hierarchyCollection->get([])
        );

        $this->hierarchyCollection->remove(['root', 'item1']);
        $this->assertSame(
            [
                'root' => []
            ],
            $this->hierarchyCollection->get([])
        );
    }

    public function testRemoveUnknown()
    {
        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root', 'item1'], 'item2');

        // do test
        $this->hierarchyCollection->remove(['root', 'unknown1', 'unknown2']);
        $this->assertSame(
            [
                'root' => [
                    'item1' => [
                        'item2' => []
                    ]
                ]
            ],
            $this->hierarchyCollection->get([])
        );
        $this->hierarchyCollection->remove([]);
        $this->assertSame(
            [
                'root' => [
                    'item1' => [
                        'item2' => []
                    ]
                ]
            ],
            $this->hierarchyCollection->get([])
        );
    }
}
