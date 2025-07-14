<?php

namespace Oro\Component\Layout\Tests\Unit;

use Oro\Component\Layout\Exception\LogicException;
use Oro\Component\Layout\HierarchyCollection;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class HierarchyCollectionTest extends TestCase
{
    private HierarchyCollection $hierarchyCollection;

    #[\Override]
    protected function setUp(): void
    {
        $this->hierarchyCollection = new HierarchyCollection();
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue($this->hierarchyCollection->isEmpty());

        $this->hierarchyCollection->add([], 'root');
        $this->assertFalse($this->hierarchyCollection->isEmpty());

        $this->hierarchyCollection->remove(['root']);
        $this->assertTrue($this->hierarchyCollection->isEmpty());
    }

    public function testClear(): void
    {
        $this->hierarchyCollection->add([], 'root');

        $this->hierarchyCollection->clear();
        $this->assertTrue($this->hierarchyCollection->isEmpty());
    }

    public function testGetRootId(): void
    {
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item');

        $this->assertEquals('root', $this->hierarchyCollection->getRootId());
    }

    public function testGetRootIdForEmptyHierarchy(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The root item does not exist.');

        $this->hierarchyCollection->getRootId();
    }

    public function testGet(): void
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

    public function testAddToFirstHierarchyLevelWithoutRoot(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot add "item1" item to "root" because "root" root item does not exist.');

        $this->hierarchyCollection->add(['root'], 'item1');
    }

    public function testAddWithoutRoot(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot add "item3" item to "root/item1/item2" because "root" root item does not exist.'
        );

        $this->hierarchyCollection->add(['root', 'item1', 'item2'], 'item3');
    }

    public function testAddToUnknown(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot add "item3" item to "root/unknown/item2" because "root" item does not have "unknown" child.'
        );

        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root', 'item1'], 'item2');

        // do test
        $this->hierarchyCollection->add(['root', 'unknown', 'item2'], 'item3');
    }

    public function testAddDuplicate(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot add "item1" item to "root" because such item already exists.');

        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root', 'item1'], 'item2');

        // do test
        $this->hierarchyCollection->add(['root'], 'item1');
    }

    public function testAdd(): void
    {
        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root'], 'item2');

        // do test
        $this->hierarchyCollection->add(['root'], 'item3');
        $this->assertSame(
            [
                'root' => [
                    'item1' => [],
                    'item2' => [],
                    'item3' => []
                ]
            ],
            $this->hierarchyCollection->get([])
        );
    }

    public function testAddToTheBeginning(): void
    {
        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root'], 'item2');

        // do test
        $this->hierarchyCollection->add(['root'], 'item3', null, true);
        $this->assertSame(
            [
                'root' => [
                    'item3' => [],
                    'item1' => [],
                    'item2' => []
                ]
            ],
            $this->hierarchyCollection->get([])
        );
    }

    public function testAddToTheBeginningOfEmpty(): void
    {
        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');

        // do test
        $this->hierarchyCollection->add(['root'], 'item1', null, true);
        $this->assertSame(
            [
                'root' => [
                    'item1' => []
                ]
            ],
            $this->hierarchyCollection->get([])
        );
    }

    public function testAddWithUnknownSibling(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot add "item3" item to "root/header" because "unknown" sibling item does not exist.'
        );

        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'header');
        $this->hierarchyCollection->add(['root', 'header'], 'item1');
        $this->hierarchyCollection->add(['root', 'header'], 'item2');

        // do test
        $this->hierarchyCollection->add(['root', 'header'], 'item3', 'unknown');
    }

    public function testAddAfterSibling(): void
    {
        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root'], 'item2');

        // do test
        $this->hierarchyCollection->add(['root'], 'item3', 'item1');
        $this->hierarchyCollection->add(['root'], 'item4', 'item2');
        $this->assertSame(
            [
                'root' => [
                    'item1' => [],
                    'item3' => [],
                    'item2' => [],
                    'item4' => []
                ]
            ],
            $this->hierarchyCollection->get([])
        );
    }

    public function testAddBeforeSibling(): void
    {
        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root'], 'item2');

        // do test
        $this->hierarchyCollection->add(['root'], 'item3', 'item2', true);
        $this->hierarchyCollection->add(['root'], 'item4', 'item1', true);
        $this->assertSame(
            [
                'root' => [
                    'item4' => [],
                    'item1' => [],
                    'item3' => [],
                    'item2' => []
                ]
            ],
            $this->hierarchyCollection->get([])
        );
    }

    public function testAddLast(): void
    {
        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root'], 'item2');

        // do test
        $this->hierarchyCollection->add(['root'], 'item3', null, false);
        $this->hierarchyCollection->add(['root'], 'item4');
        $this->assertSame(
            [
                'root' => [
                    'item1' => [],
                    'item2' => [],
                    'item4' => [],
                    'item3' => [],
                ]
            ],
            $this->hierarchyCollection->get([])
        );
    }

    public function testAddFirst(): void
    {
        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root'], 'item2');

        // do test
        $this->hierarchyCollection->add(['root'], 'item3');
        $this->hierarchyCollection->add(['root'], 'item4', null, true);
        $this->assertSame(
            [
                'root' => [
                    'item4' => [],
                    'item1' => [],
                    'item2' => [],
                    'item3' => [],
                ]
            ],
            $this->hierarchyCollection->get([])
        );
    }

    public function testMove(): void
    {
        // prepare hierarchy
        $this->hierarchyCollection->add([], 'root');
        $this->hierarchyCollection->add(['root'], 'item1');
        $this->hierarchyCollection->add(['root', 'item1'], 'item11');
        $this->hierarchyCollection->add(['root'], 'item2');
        $this->hierarchyCollection->add(['root', 'item2'], 'item21');
        $this->hierarchyCollection->add(['root'], 'item3');
        $this->hierarchyCollection->add(['root', 'item3'], 'item31');

        // do test
        $movingItem = $this->hierarchyCollection->get(['root', 'item2']);
        $this->hierarchyCollection->remove(['root', 'item2']);
        $this->hierarchyCollection->add(['root'], 'item2', 'item1', true, $movingItem);
        $this->assertSame(
            [
                'root' => [
                    'item2' => ['item21' => []],
                    'item1' => ['item11' => []],
                    'item3' => ['item31' => []]
                ]
            ],
            $this->hierarchyCollection->get([])
        );
    }

    public function testRemove(): void
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

    public function testRemoveUnknown(): void
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
