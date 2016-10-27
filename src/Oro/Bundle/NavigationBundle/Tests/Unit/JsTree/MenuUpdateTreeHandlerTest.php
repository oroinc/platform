<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\EventListener;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\NavigationBundle\JsTree\MenuUpdateTreeHandler;

class MenuUpdateTreeHandlerTest extends \PHPUnit_Framework_TestCase
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
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    protected function setUp()
    {
        $this->translator = $this->getMock(TranslatorInterface::class);

        $this->factory = new MenuFactory();

        $this->handler = new MenuUpdateTreeHandler($this->translator);
    }

    /**
     * @dataProvider testCreateTreeDataProvider
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

    public function testCreateTreeDataProvider()
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
}
