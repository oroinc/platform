<?php

namespace Oro\Bundle\UIBundle\Tests\Model;

use Oro\Bundle\UIBundle\Model\TreeItem;

class TreeItemTest extends \PHPUnit\Framework\TestCase
{
    public function testKey()
    {
        $treeItem = new Treeitem('key', 'Label');
        $this->assertEquals('key', $treeItem->getKey());
    }

    public function testLabel()
    {
        $treeItem = new Treeitem('key', 'Label');
        $this->assertEquals('Label', $treeItem->getLabel());
    }

    public function testParent()
    {
        $parentTreeItem = new Treeitem('parent', 'Parent');

        $treeItem = new Treeitem('key', 'Label');
        $treeItem->setParent($parentTreeItem);

        $this->assertEquals($parentTreeItem, $treeItem->getParent());
    }

    /**
     * @dataProvider getParentsProvider
     *
     * @param TreeItem $item
     * @param bool $includeRoot
     * @param TreeItem[] $expectedParents
     */
    public function testGetParents($item, $includeRoot, array $expectedParents)
    {
        $this->assertEquals($expectedParents, $item->getParents($includeRoot));
    }

    public function getParentsProvider()
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

    public function testChildren()
    {
        $parentTreeItem = new Treeitem('parent', 'Parent');

        $treeItem = new Treeitem('key', 'Label');
        $treeItem->setParent($parentTreeItem);

        $childTreeItem = new Treeitem('child', 'Child');
        $parentTreeItem->addChild($childTreeItem);

        $this->assertEquals(['key' => $treeItem, 'child' => $childTreeItem], $parentTreeItem->getChildren());
    }

    public function testToString()
    {
        $treeItem = new Treeitem('key', 'Label');
        $this->assertEquals('Label', (string) $treeItem);
    }
}
