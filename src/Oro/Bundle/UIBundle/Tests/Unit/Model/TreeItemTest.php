<?php

namespace Oro\Bundle\UIBundle\Tests\Model;

use Oro\Bundle\UIBundle\Model\TreeItem;

class TreeItemTest extends \PHPUnit_Framework_TestCase
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

    public function testChildren()
    {
        $parentTreeItem = new Treeitem('parent', 'Parent');

        $treeItem = new Treeitem('key', 'Label');
        $treeItem->setParent($parentTreeItem);

        $childTreeItem = new Treeitem('child', 'Child');
        $parentTreeItem->addChild($childTreeItem);

        $this->assertEquals(['key' => $treeItem, 'child' => $childTreeItem], $parentTreeItem->getChildren());
    }

    public function testLevel()
    {
        $firstLevelTreeItem = new Treeitem('first', 'First');
        $secondLevelTreeItem = new Treeitem('second', 'Second');
        $secondLevelTreeItem->setParent($firstLevelTreeItem);
        $thirdLevelTreeItem = new Treeitem('third', 'Third');
        $thirdLevelTreeItem->setParent($secondLevelTreeItem);

        $this->assertEquals(2, $thirdLevelTreeItem->getLevel());
    }

    public function testToString()
    {
        $treeItem = new Treeitem('key', 'Label');
        $this->assertEquals('Label', (string) $treeItem);
    }
}
