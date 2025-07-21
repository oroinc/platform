<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Model;

use Oro\Bundle\UIBundle\Model\TreeItem;
use PHPUnit\Framework\TestCase;

class TreeItemTest extends TestCase
{
    public function testKey(): void
    {
        $treeItem = new Treeitem('key', 'Label');
        $this->assertEquals('key', $treeItem->getKey());
    }

    public function testLabel(): void
    {
        $treeItem = new Treeitem('key', 'Label');
        $this->assertEquals('Label', $treeItem->getLabel());
    }

    public function testParent(): void
    {
        $parentTreeItem = new Treeitem('parent', 'Parent');

        $treeItem = new Treeitem('key', 'Label');
        $treeItem->setParent($parentTreeItem);

        $this->assertEquals($parentTreeItem, $treeItem->getParent());
    }

    /**
     * @dataProvider getParentsProvider
     */
    public function testGetParents(TreeItem $item, bool $includeRoot, array $expectedParents): void
    {
        $this->assertEquals($expectedParents, $item->getParents($includeRoot));
    }

    public function getParentsProvider(): array
    {
        $bazItem = new TreeItem('baz');

        $barItem = new TreeItem('bar');
        $barItem->setParent($bazItem);

        $fooItem = new TreeItem('foo');
        $fooItem->setParent($barItem);

        return [
            'with no parents' => [
                'item' => new TreeItem('foo'),
                'includeRoot' => false,
                'expectedParents' => [],
            ],
            'without root' => [
                'item' => $fooItem,
                'includeRoot' => false,
                'expectedParents' => [$barItem],
            ],
            'with root' => [
                'item' => $fooItem,
                'includeRoot' => true,
                'expectedParents' => [$bazItem, $barItem],
            ],
        ];
    }

    public function testChildren(): void
    {
        $parentTreeItem = new Treeitem('parent', 'Parent');

        $treeItem = new Treeitem('key', 'Label');
        $treeItem->setParent($parentTreeItem);

        $childTreeItem = new Treeitem('child', 'Child');
        $parentTreeItem->addChild($childTreeItem);

        $this->assertEquals(['key' => $treeItem, 'child' => $childTreeItem], $parentTreeItem->getChildren());
    }

    public function testToString(): void
    {
        $treeItem = new Treeitem('key', 'Label');
        $this->assertEquals('Label', (string) $treeItem);
    }
}
