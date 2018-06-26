<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Oro\Bundle\NavigationBundle\JsTree\MenuUpdateTreeHandler;
use Oro\Bundle\UIBundle\Model\TreeItem;
use Symfony\Component\Translation\TranslatorInterface;

class MenuUpdateTreeHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MenuUpdateTreeHandler
     */
    protected $handler;

    /**
     * @var MenuFactory
     */
    protected $factory;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->factory = new MenuFactory();

        $this->handler = new MenuUpdateTreeHandler($this->translator);
    }

    /**
     * @dataProvider getTreeDataProvider
     * @param bool  $includeRoot
     * @param array $expectedTree
     */
    public function testCreateTree($includeRoot, array $expectedTree)
    {
        /** @var ItemInterface $root */
        $root = $this->factory->createItem('root');
        $root
            ->addChild('item1')
            ->addChild('item1-1', ['extras' => ['read_only' => true]]);
        $root->addChild('item2')->setDisplay(false);

        $tree = $this->handler->createTree($root, $includeRoot);
        $this->assertEquals(
            $expectedTree,
            $tree
        );
    }

    /**
     * @dataProvider getTreeDataProvider
     * @param bool  $includeRoot
     * @param array $expectedTreeItemList
     */
    public function testGetTreeItemList($includeRoot, array $expectedTreeItemList)
    {
        /** @var ItemInterface $root */
        $root = $this->factory->createItem('root');
        $root
            ->addChild('item1')
            ->addChild('item1-1', ['extras' => ['read_only' => true]]);
        $root->addChild('item2')->setDisplay(false);

        $tree = $this->handler->createTree($root, $includeRoot);
        $this->assertEquals($expectedTreeItemList, $tree);
    }

    /**
     * @return array
     */
    public function getTreeDataProvider()
    {
        return [
            'include root' => [
                'includeRoot' => true,
                'expectedTree' => [
                    [
                        'id' => 'root',
                        'parent' => '#',
                        'text' => null,
                        'state' => [
                            'opened' => true,
                            'disabled' => false
                        ],
                        'li_attr' => []
                    ],
                    [
                        'id' => 'item1',
                        'parent' => 'root',
                        'text' => null,
                        'state' => [
                            'opened' => false,
                            'disabled' => false
                        ],
                        'li_attr' => []
                    ],
                    [
                        'id' => 'item1-1',
                        'parent' => 'item1',
                        'text' => null,
                        'state' => [
                            'opened' => false,
                            'disabled' => true
                        ],
                        'li_attr' => []
                    ],
                    [
                        'id' => 'item2',
                        'parent' => 'root',
                        'text' => null,
                        'state' => [
                            'opened' => false,
                            'disabled' => false
                        ],
                        'li_attr' => ['class' => 'hidden']
                    ]
                ]
            ],
            'not include root' => [
                'includeRoot' => false,
                'expectedTree' => [
                    [
                        'id' => 'item1',
                        'parent' => 'root',
                        'text' => null,
                        'state' => [
                            'opened' => false,
                            'disabled' => false
                        ],
                        'li_attr' => []
                    ],
                    [
                        'id' => 'item1-1',
                        'parent' => 'item1',
                        'text' => null,
                        'state' => [
                            'opened' => false,
                            'disabled' => true
                        ],
                        'li_attr' => []
                    ],
                    [
                        'id' => 'item2',
                        'parent' => 'root',
                        'text' => null,
                        'state' => [
                            'opened' => false,
                            'disabled' => false
                        ],
                        'li_attr' => ['class' => 'hidden']
                    ]
                ]
            ]
        ];
    }

    /**
     * @return array
     */
    public function getTreeItemListDataProvider()
    {
        $root = new TreeItem('root', 'root');
        $item1 = new TreeItem('item1', 'item1');
        $item11 = new TreeItem('item1-1', 'item1-1');
        $item2 = new TreeItem('item2', 'item2');


        return [
            'include root' => [
                'includeRoot' => true,
                'expectedTreeItemList' => [
                    $root,
                    $item1->setParent($root),
                    $item11->setParent($item1),
                    $item2->setParent($root),
                ]
            ],
            'not include root' => [
                'includeRoot' => false,
                'expectedTreeItemList' => [
                    $item1,
                    $item11->setParent($item1),
                    $item2,
                ]
            ]
        ];
    }
}
