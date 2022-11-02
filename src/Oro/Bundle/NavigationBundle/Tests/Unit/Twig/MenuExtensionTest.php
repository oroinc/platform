<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuItem;
use Knp\Menu\Provider\MenuProviderInterface;
use Knp\Menu\Twig\Helper;
use Oro\Bundle\NavigationBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\NavigationBundle\Menu\BreadcrumbManagerInterface;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Twig\MenuExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\Assert;
use Twig\Environment;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MenuExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $helper;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $breadcrumbManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configurationProvider;

    /** @var MenuExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->breadcrumbManager = $this->createMock(BreadcrumbManagerInterface::class);
        $this->helper = $this->createMock(Helper::class);
        $this->provider = $this->createMock(MenuProviderInterface::class);
        $this->configurationProvider = $this->createMock(ConfigurationProvider::class);

        $container = self::getContainerBuilder()
            ->add(Helper::class, $this->helper)
            ->add(BuilderChainProvider::class, $this->provider)
            ->add(BreadcrumbManagerInterface::class, $this->breadcrumbManager)
            ->add(ConfigurationProvider::class, $this->configurationProvider)
            ->getContainer($this);

        $this->extension = new MenuExtension($container);
    }

    public function testRenderBreadCrumbs()
    {
        $environment = $this->createMock(Environment::class);

        $this->breadcrumbManager->expects($this->once())
            ->method('getBreadcrumbs')
            ->willReturn(['test-breadcrumb']);

        $result = 'test';
        $environment->expects($this->once())
            ->method('render')
            ->with(
                '@OroNavigation/Menu/breadcrumbs.html.twig',
                [
                    'breadcrumbs' => [
                        'test-breadcrumb'
                    ],
                    'useDecorators' => true
                ]
            )->willReturn($result);

        self::assertEquals(
            $result,
            self::callTwigFunction($this->extension, 'oro_breadcrumbs', [$environment, 'test_menu'])
        );
    }

    public function testWrongBredcrumbs()
    {
        $environment = $this->createMock(Environment::class);

        $this->breadcrumbManager->expects($this->once())
            ->method('getBreadcrumbs')
            ->willReturn(null);

        $this->assertNull(
            self::callTwigFunction($this->extension, 'oro_breadcrumbs', [$environment, 'test_menu'])
        );
    }

    public function testGetMenuAsString()
    {
        $options = [];
        $menu = 'test';
        $menuInstance = $this->assertGetMenuString($menu, 'path', $options);
        $this->assertSame(
            $menuInstance,
            self::callTwigFunction($this->extension, 'oro_menu_get', [$menu, ['path'], $options])
        );
    }

    public function testGetMenuException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The menu has no child named "path"');

        $options = [];
        $menuInstance = $this->createMock(ItemInterface::class);
        $menuInstance->expects($this->once())
            ->method('getChild')
            ->with('path')
            ->willReturn(null);

        self::callTwigFunction($this->extension, 'oro_menu_get', [$menuInstance, ['path'], $options]);
    }

    public function testRenderException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The array cannot be empty');

        self::callTwigFunction($this->extension, 'oro_menu_render', [[]]);
    }

    public function testRenderMenuInstance()
    {
        $options = [];
        $renderer = 'test';
        $menuInstance = $this->createMock(ItemInterface::class);
        $menuInstance->expects($this->once())
            ->method('getExtra')
            ->with('type');
        $menuInstance->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator());
        $this->assertRender($menuInstance, $menuInstance, $options, $renderer);
    }

    public function testRenderMenuAsArray()
    {
        $options = [];
        $renderer = 'test';
        $menu = ['path', 'test'];
        $menuInstance = $this->assertGetMenuString('path', 'test', $options);
        $menuInstance->expects($this->once())
            ->method('getExtra')
            ->with('type');
        $menuInstance->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator());
        $this->assertRender($menu, $menuInstance, $options, $renderer);
    }

    /**
     * @dataProvider typeOptionsDataProvider
     */
    public function testRenderMenuInstanceWithExtra(array $options)
    {
        $renderer = 'test';
        $menuInstance = $this->createMock(ItemInterface::class);
        $menuInstance->expects($this->once())
            ->method('getExtra')
            ->with('type')
            ->willReturn('type');
        $menuInstance->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator());

        $runtimeOptions = [
            'template' => 'test_runtime.tpl'
        ];
        $this->configurationProvider->expects(self::once())
            ->method('getMenuTemplates')
            ->willReturn($options);

        $this->assertRender($menuInstance, $menuInstance, $runtimeOptions, $renderer);
    }

    public function typeOptionsDataProvider(): array
    {
        return [
            'empty' => [
                []
            ],
            'has type config' => [
                [
                    'templates' => [
                        'type' => [
                            'template' => 'test2.tpl'
                        ]
                    ]
                ]
            ],
            'has other type config' => [
                [
                    'templates' => [
                        'type_no' => [
                            'template' => 'test2.tpl'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider menuItemsDataProvider
     */
    public function testFilterNotAllowedItems(array $items, array $expected)
    {
        $menu = $this->createMock(ItemInterface::class);

        $menu->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($items));

        $this->helper->expects($this->once())
            ->method('render')
            ->willReturnCallback(function ($menu) use ($expected) {
                $result = $this->collectResultItemsData($menu);
                Assert::assertEquals($expected, $result);
                return '';
            });

        self::callTwigFunction($this->extension, 'oro_menu_render', [$menu]);
    }

    private function collectResultItemsData(ItemInterface $item): array
    {
        $result = [];
        /** @var ItemInterface $sub */
        foreach ($item as $sub) {
            $result[] = [
                'label' => $sub->getLabel(),
                'uri' => $sub->getUri(),
                'isAllowed' => $sub->getExtra('isAllowed'),
                'isDisplayed' => $sub->isDisplayed(),
                'children' => $this->collectResultItemsData($sub)
            ];
        }

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function menuItemsDataProvider(): array
    {
        return [
            [
                [
                    $this->getMenuItem('item_1'),
                    $this->getMenuItem(
                        'item_2',
                        true,
                        [
                            $this->getMenuItem('item_2_1'),
                            $this->getMenuItem(''),
                        ]
                    ),
                    $this->getMenuItem(
                        'item_3',
                        true,
                        [
                            $this->getMenuItem('item_3_1', false),
                            $this->getMenuItem('item_3_2'),
                        ]
                    ),
                    $this->getMenuItem(
                        'item_4',
                        true,
                        [
                            $this->getMenuItem('item_4_1', false),
                            $this->getMenuItem(''),
                        ]
                    ),
                    $this->getMenuItem(
                        'item_5',
                        true,
                        [
                            $this->getMenuItem(
                                'item_5_1',
                                true,
                                [
                                    $this->getMenuItem('item_5_1_1', false),
                                ]
                            )
                        ],
                        '#'
                    ),
                    $this->getMenuItem(
                        'item_6',
                        true,
                        [
                            $this->getMenuItem('item_6_1', false),
                            $this->getMenuItem(''),
                        ],
                        '/my-uri'
                    ),
                    $this->getMenuItem(
                        'item_7',
                        true,
                        [
                            $this->getMenuItem('item_7_1', false, [], '', true),
                            $this->getMenuItem('item_7_2', true, [], '', false),
                        ],
                        '#'
                    ),
                ],
                [
                    [
                        'label' => 'item_1',
                        'uri' => '',
                        'isAllowed' => true,
                        'isDisplayed' => true,
                        'children' => []
                    ],
                    [
                        'label' => 'item_2',
                        'uri' => '',
                        'isAllowed' => true,
                        'isDisplayed' => true,
                        'children' => [
                            [
                                'label' => 'item_2_1',
                                'uri' => '',
                                'isAllowed' => true,
                                'isDisplayed' => true,
                                'children' => []
                            ],
                            [
                                'label' => '',
                                'uri' => '',
                                'isAllowed' => true,
                                'isDisplayed' => true,
                                'children' => []
                            ],
                        ]
                    ],
                    [
                        'label' => 'item_3',
                        'uri' => '',
                        'isAllowed' => true,
                        'isDisplayed' => true,
                        'children' => [
                            [
                                'label' => 'item_3_1',
                                'uri' => '',
                                'isAllowed' => false,
                                'isDisplayed' => true,
                                'children' => []
                            ],
                            [
                                'label' => 'item_3_2',
                                'uri' => '',
                                'isAllowed' => true,
                                'isDisplayed' => true,
                                'children' => []
                            ],
                        ]
                    ],
                    [
                        'label' => 'item_4',
                        'uri' => '',
                        'isAllowed' => false,
                        'isDisplayed' => true,
                        'children' => [
                            [
                                'label' => 'item_4_1',
                                'uri' => '',
                                'isAllowed' => false,
                                'isDisplayed' => true,
                                'children' => []
                            ],
                            [
                                'label' => '',
                                'uri' => '',
                                'isAllowed' => true,
                                'isDisplayed' => true,
                                'children' => []
                            ],
                        ]
                    ],
                    [
                        'label' => 'item_5',
                        'uri' => '#',
                        'isAllowed' => false,
                        'isDisplayed' => true,
                        'children' => [
                            [
                                'label' => 'item_5_1',
                                'uri' => '',
                                'isAllowed' => false,
                                'isDisplayed' => true,
                                'children' => [
                                    [
                                        'label' => 'item_5_1_1',
                                        'uri' => '',
                                        'isAllowed' => false,
                                        'isDisplayed' => true,
                                        'children' => []
                                    ],
                                ]
                            ]
                        ]
                    ],
                    [
                        'label' => 'item_6',
                        'uri' => '/my-uri',
                        'isAllowed' => true,
                        'isDisplayed' => true,
                        'children' => [
                            [
                                'label' => 'item_6_1',
                                'uri' => '',
                                'isAllowed' => false,
                                'isDisplayed' => true,
                                'children' => []
                            ],
                            [
                                'label' => '',
                                'uri' => '',
                                'isAllowed' => true,
                                'isDisplayed' => true,
                                'children' => []
                            ],
                        ]
                    ],
                    [
                        'label' => 'item_7',
                        'uri' => '#',
                        'isAllowed' => false,
                        'isDisplayed' => true,
                        'children' => [
                            [
                                'label' => 'item_7_1',
                                'uri' => '',
                                'isAllowed' => false,
                                'isDisplayed' => true,
                                'children' => []
                            ],
                            [
                                'label' => 'item_7_2',
                                'uri' => '',
                                'isAllowed' => true,
                                'isDisplayed' => false,
                                'children' => []
                            ],
                        ]
                    ]
                ]
            ]
        ];
    }

    private function getMenuItem(
        string $label,
        bool $isAllowed = true,
        array $children = [],
        string $uri = '',
        bool $isDisplayed = true
    ): MenuItem {
        $menu = $this->getMockBuilder(MenuItem::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getLabel', 'getUri', 'hasChildren', 'getChildren', 'getIterator', 'count'])
            ->getMock();
        $menu->expects($this->any())
            ->method('getLabel')
            ->willReturn($label);
        $menu->expects($this->any())
            ->method('getUri')
            ->willReturn($uri);
        $menu->setExtra('isAllowed', $isAllowed);
        $menu->setDisplay($isDisplayed);

        $childrenCount = count($children);
        $hasChildren = $childrenCount > 0;
        $menu->expects($this->any())
            ->method('hasChildren')
            ->willReturn($hasChildren);
        $menu->expects($this->any())
            ->method('hasChildren')
            ->willReturn($hasChildren);
        $menu->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($children));
        $menu->expects($this->any())
            ->method('count')
            ->willReturn($childrenCount);
        $menu->expects($this->any())
            ->method('getChildren')
            ->willReturn($children);

        return $menu;
    }

    private function assertRender($menu, $menuInstance, $options, $renderer)
    {
        $this->helper->expects($this->once())
            ->method('render')
            ->with($menuInstance, $options, $renderer)
            ->willReturn('MENU');

        $this->assertEquals(
            'MENU',
            self::callTwigFunction($this->extension, 'oro_menu_render', [$menu, $options, $renderer])
        );
    }

    /**
     * @return ItemInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function assertGetMenuString(string $menu, string $path, array $options)
    {
        $menuInstance = $this->createMock(ItemInterface::class);
        $menuInstance->expects($this->once())
            ->method('getChild')
            ->with($path)
            ->willReturnSelf();
        $this->provider->expects($this->once())
            ->method('get')
            ->with($menu, $options)
            ->willReturn($menuInstance);

        return $menuInstance;
    }
}
