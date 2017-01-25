<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Utils;

use Oro\Bundle\UIBundle\Model\TreeItem;
use Oro\Bundle\UIBundle\Utils\TreeUtils;

class TreeUtilsTest extends \PHPUnit_Framework_TestCase
{
    public function testBuildFlatTreeItemList()
    {
        $tree = new ExternalTreeItemStub('root', 'Root', [
            new ExternalTreeItemStub('item1', 'Item 1', [
                new ExternalTreeItemStub('item11', 'Item 1 1', [
                    new ExternalTreeItemStub('item111', 'Item 1 1 1'),
                ]),
                new ExternalTreeItemStub('item12', 'Item 1 2'),
            ]),
            new ExternalTreeItemStub('item2', 'Item 2'),
        ]);

        $item1 = new TreeItem('item1', 'Item 1');
        $item2 = new TreeItem('item2', 'Item 2');
        $item11 = new TreeItem('item11', 'Item 1 1');
        $item11->setParent($item1);
        $item12 = new TreeItem('item12', 'Item 1 2');
        $item12->setParent($item1);
        $item111 = new TreeItem('item111', 'Item 1 1 1');
        $item111->setParent($item11);

        $result = [
            'item1' => $item1,
            'item2' => $item2,
            'item11' => $item11,
            'item12' => $item12,
            'item111' => $item111,
        ];

        $this->assertEquals($result, TreeUtils::buildFlatTreeItemList($tree, 'id', 'title'));
    }
}
