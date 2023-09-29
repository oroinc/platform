<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\JsTree;

use Knp\Menu\MenuFactory;
use Oro\Bundle\NavigationBundle\JsTree\MenuUpdateTreeHandler;
use Oro\Bundle\UIBundle\Model\TreeItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class MenuUpdateTreeHandlerTest extends TestCase
{
    private MenuFactory $factory;

    private TranslatorInterface|MockObject $translator;

    private MenuUpdateTreeHandler $handler;

    protected function setUp(): void
    {
        $this->factory = new MenuFactory();
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->handler = new MenuUpdateTreeHandler($this->translator);
    }

    /**
     * @dataProvider getTreeDataProvider
     */
    public function testCreateTree(bool $includeRoot, array $expectedTree): void
    {
        $root = $this->factory->createItem('root');
        $root
            ->addChild('item1')
            ->addChild('item1-1', ['extras' => ['read_only' => true]]);
        $root->addChild('item2')->setDisplay(false);

        $tree = $this->handler->createTree($root, $includeRoot);
        self::assertEquals(
            $expectedTree,
            $tree
        );
    }

    /**
     * @dataProvider getTreeDataProvider
     */
    public function testGetTreeItemList(bool $includeRoot, array $expectedTreeItemList): void
    {
        $root = $this->factory->createItem('root');
        $root
            ->addChild('item1')
            ->addChild('item1-1', ['extras' => ['read_only' => true]]);
        $root->addChild('item2')->setDisplay(false);

        $tree = $this->handler->createTree($root, $includeRoot);
        self::assertEquals($expectedTreeItemList, $tree);
    }

    public function getTreeDataProvider(): array
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

    public function testCreateForEmptyRoot(): void
    {
        self::assertEquals([], $this->handler->createTree(null, true));
    }

    public function testCreateForNotAllowedRoot(): void
    {
        $root = $this->factory->createItem('root', [
            'extras' => [
                MenuUpdateTreeHandler::EXTRA_IS_ALLOWED_FOR_BACKOFFICE => false
            ]
        ]);
        $root->addChild('item1');
        self::assertEquals([
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
        ], $this->handler->createTree($root, true));
    }

    public function testCreateForNotAllowedChild(): void
    {
        $root = $this->factory->createItem('root');
        $root
            ->addChild('item1', [
                'extras' => [
                    MenuUpdateTreeHandler::EXTRA_IS_ALLOWED_FOR_BACKOFFICE => false
                ]
            ]);

        $tree = $this->handler->createTree($root, true);
        self::assertEquals([
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
        ], $tree);
    }

    public function getTreeItemListDataProvider(): array
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
