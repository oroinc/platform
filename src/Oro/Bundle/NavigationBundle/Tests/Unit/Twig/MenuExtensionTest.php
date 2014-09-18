<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

use Knp\Menu\ItemInterface;
use Oro\Bundle\NavigationBundle\Twig\MenuExtension;

class MenuExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject $helper
     */
    protected $helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $breadcrumbManager;

    /**
     * @var MenuExtension $menuExtension
     */
    protected $menuExtension;

    protected function setUp()
    {
        $this->breadcrumbManager = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Menu\BreadcrumbManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->helper = $this->getMockBuilder('Knp\Menu\Twig\Helper')
            ->disableOriginalConstructor()
            ->setMethods(array('render'))
            ->getMock();

        $this->provider = $this->getMockBuilder('Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->menuExtension = new MenuExtension($this->helper, $this->provider, $this->breadcrumbManager);
    }

    public function testGetFunctions()
    {
        $functions = $this->menuExtension->getFunctions();
        $this->assertArrayHasKey('oro_menu_render', $functions);
        $this->assertInstanceOf('Twig_Function_Method', $functions['oro_menu_render']);
        $this->assertAttributeEquals('render', 'method', $functions['oro_menu_render']);

        $this->assertArrayHasKey('oro_menu_get', $functions);
        $this->assertInstanceOf('Twig_Function_Method', $functions['oro_menu_get']);
        $this->assertAttributeEquals('getMenu', 'method', $functions['oro_menu_get']);
    }

    public function testGetName()
    {
        $this->assertEquals(MenuExtension::MENU_NAME, $this->menuExtension->getName());
    }

    public function testRenderBreadCrumbs()
    {
        $environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $template = $this->getMockBuilder('\Twig_TemplateInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->breadcrumbManager->expects($this->once())
            ->method('getBreadcrumbs')
            ->will($this->returnValue(array('test-breadcrumb')));

        $environment->expects($this->once())
            ->method('loadTemplate')
            ->will($this->returnValue($template));

        $template->expects($this->once())
            ->method('render')
            ->with(
                array(
                    'breadcrumbs' => array(
                        'test-breadcrumb'
                    ),
                    'useDecorators' => true
                )
            );
        ;
        $this->menuExtension->renderBreadCrumbs($environment, 'test_menu');
    }

    public function testWrongBredcrumbs()
    {

        $environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();

        $this->breadcrumbManager->expects($this->once())
            ->method('getBreadcrumbs')
            ->will($this->returnValue(null));

        $this->assertNull($this->menuExtension->renderBreadCrumbs($environment, 'test_menu'));
    }

    public function testGetMenuAsString()
    {
        $options = array();
        $menu = 'test';
        $menuInstance = $this->assertGetMenuString($menu, 'path', $options);
        $this->assertSame($menuInstance, $this->menuExtension->getMenu($menu, array('path'), $options));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The menu has no child named "path"
     */
    public function testGetMenuException()
    {
        $options = array();
        $menuInstance = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->setMethods(array('getChild'))
            ->getMockForAbstractClass();
        $menuInstance->expects($this->once())
            ->method('getChild')
            ->with('path')
            ->will($this->returnValue(null));
        $this->menuExtension->getMenu($menuInstance, array('path'), $options);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The array cannot be empty
     */
    public function testRenderException()
    {
        $this->menuExtension->render(array());
    }

    public function testRenderMenuInstance()
    {
        $options = array();
        $renderer = 'test';
        $menuInstance = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->setMethods(array('getExtra'))
            ->getMockForAbstractClass();
        $menuInstance->expects($this->once())
            ->method('getExtra')
            ->with('type');
        $menuInstance->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator()));
        $this->assertRender($menuInstance, $menuInstance, $options, $renderer);
    }

    public function testRenderMenuAsArray()
    {
        $options = array();
        $renderer = 'test';
        $menu = array('path', 'test');
        $menuInstance = $this->assertGetMenuString('path', 'test', $options);
        $menuInstance->expects($this->once())
            ->method('getExtra')
            ->with('type');
        $menuInstance->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator()));
        $this->assertRender($menu, $menuInstance, $options, $renderer);
    }

    /**
     * @dataProvider typeOptionsDataProvider
     * @param array $options
     */
    public function testRenderMenuInstanceWithExtra($options)
    {
        $renderer = 'test';
        $menuInstance = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->setMethods(array('getExtra'))
            ->getMockForAbstractClass();
        $menuInstance->expects($this->once())
            ->method('getExtra')
            ->with('type')
            ->will($this->returnValue('type'));
        $menuInstance->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator()));

        $runtimeOptions = array(
            'template' => 'test_runtime.tpl'
        );
        $this->menuExtension->setMenuConfiguration($options);
        $this->assertRender($menuInstance, $menuInstance, $runtimeOptions, $renderer);
    }

    public function typeOptionsDataProvider()
    {
        return array(
            'empty' => array(
                array()
            ),
            'has type config' => array(
                array(
                    'templates' => array(
                        'type' => array(
                            'template' => 'test2.tpl'
                        )
                    )
                )
            ),
            'has other type config' => array(
                array(
                    'templates' => array(
                        'type_no' => array(
                            'template' => 'test2.tpl'
                        )
                    )
                )
            )
        );
    }

    /**
     * @dataProvider menuItemsDataProvider
     * @param array $items
     * @param array $expected
     */
    public function testFilterUnallowedItems($items, $expected)
    {
        $menu = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->getMockForAbstractClass();

        $menu->expects($this->atLeastOnce())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator($items)));

        $this->helper->expects($this->once())
            ->method('render')
            ->will(
                $this->returnCallback(
                    function ($menu) use ($expected) {
                        $result = $this->collectResultItemsData($menu);
                        \PHPUnit_Framework_Assert::assertEquals($expected, $result);
                    }
                )
            );

        $this->menuExtension->render($menu);
    }

    protected function collectResultItemsData($item)
    {
        $result = array();
        /** @var ItemInterface $sub */
        foreach ($item as $sub) {
            $result[] = array(
                'label' => $sub->getLabel(),
                'uri' => $sub->getUri(),
                'isAllowed' => $sub->getExtra('isAllowed'),
                'children' => $this->collectResultItemsData($sub)
            );
        }

        return $result;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function menuItemsDataProvider()
    {
        return array(
            array(
                array(
                    $this->getMenuItem('item_1'),
                    $this->getMenuItem(
                        'item_2',
                        true,
                        array(
                            $this->getMenuItem('item_2_1'),
                            $this->getMenuItem(''),
                        )
                    ),
                    $this->getMenuItem(
                        'item_3',
                        true,
                        array(
                            $this->getMenuItem('item_3_1', false),
                            $this->getMenuItem('item_3_2'),
                        )
                    ),
                    $this->getMenuItem(
                        'item_4',
                        true,
                        array(
                            $this->getMenuItem('item_4_1', false),
                            $this->getMenuItem(''),
                        )
                    ),
                    $this->getMenuItem(
                        'item_5',
                        true,
                        array(
                            $this->getMenuItem(
                                'item_5_1',
                                true,
                                array(
                                    $this->getMenuItem('item_5_1_1', false),
                                )
                            )
                        ),
                        '#'
                    ),
                    $this->getMenuItem(
                        'item_6',
                        true,
                        array(
                            $this->getMenuItem('item_6_1', false),
                            $this->getMenuItem(''),
                        ),
                        '/my-uri'
                    ),
                ),
                array(
                    array(
                        'label' => 'item_1',
                        'uri' => '',
                        'isAllowed' => true,
                        'children' => array()
                    ),
                    array(
                        'label' => 'item_2',
                        'uri' => '',
                        'isAllowed' => true,
                        'children' => array(
                            array(
                                'label' => 'item_2_1',
                                'uri' => '',
                                'isAllowed' => true,
                                'children' => array()
                            ),
                            array(
                                'label' => '',
                                'uri' => '',
                                'isAllowed' => true,
                                'children' => array()
                            ),
                        )
                    ),
                    array(
                        'label' => 'item_3',
                        'uri' => '',
                        'isAllowed' => true,
                        'children' => array(
                            array(
                                'label' => 'item_3_1',
                                'uri' => '',
                                'isAllowed' => false,
                                'children' => array()
                            ),
                            array(
                                'label' => 'item_3_2',
                                'uri' => '',
                                'isAllowed' => true,
                                'children' => array()
                            ),
                        )
                    ),
                    array(
                        'label' => 'item_4',
                        'uri' => '',
                        'isAllowed' => false,
                        'children' => array(
                            array(
                                'label' => 'item_4_1',
                                'uri' => '',
                                'isAllowed' => false,
                                'children' => array()
                            ),
                            array(
                                'label' => '',
                                'uri' => '',
                                'isAllowed' => true,
                                'children' => array()
                            ),
                        )
                    ),
                    array(
                        'label' => 'item_5',
                        'uri' => '#',
                        'isAllowed' => false,
                        'children' => array(
                            array(
                                'label' => 'item_5_1',
                                'uri' => '',
                                'isAllowed' => false,
                                'children' => array(
                                    array(
                                        'label' => 'item_5_1_1',
                                        'uri' => '',
                                        'isAllowed' => false,
                                        'children' => array()
                                    ),
                                )
                            )
                        )
                    ),
                    array(
                        'label' => 'item_6',
                        'uri' => '/my-uri',
                        'isAllowed' => true,
                        'children' => array(
                            array(
                                'label' => 'item_6_1',
                                'uri' => '',
                                'isAllowed' => false,
                                'children' => array()
                            ),
                            array(
                                'label' => '',
                                'uri' => '',
                                'isAllowed' => true,
                                'children' => array()
                            ),
                        )
                    ),
                )
            )
        );
    }

    protected function getMenuItem($label, $isAllowed = true, $children = array(), $uri = '')
    {
        $menu = $this->getMockBuilder('Knp\Menu\MenuItem')
            ->disableOriginalConstructor()
            ->setMethods(array('getLabel', 'getUri', 'hasChildren', 'getChildren', 'getIterator', 'count'))
            ->getMock();

        $menu->expects($this->any())
            ->method('getLabel')
            ->will($this->returnValue($label));

        $menu->expects($this->any())
            ->method('getUri')
            ->will($this->returnValue($uri));

        $menu->setExtra('isAllowed', $isAllowed);

        $childrenCount = count($children);
        $hasChildren = $childrenCount > 0;
        $menu->expects($this->any())
            ->method('hasChildren')
            ->will($this->returnValue($hasChildren));

        $menu->expects($this->any())
            ->method('hasChildren')
            ->will($this->returnValue($hasChildren));

        $menu->expects($this->any())
            ->method('getIterator')
            ->will($this->returnValue(new \ArrayIterator($children)));

        $menu->expects($this->any())
            ->method('count')
            ->will($this->returnValue($childrenCount));

        $menu->expects($this->any())
            ->method('getChildren')
            ->will($this->returnValue($children));

        return $menu;
    }

    protected function assertRender($menu, $menuInstance, $options, $renderer)
    {
        $this->helper->expects($this->once())
            ->method('render')
            ->with($menuInstance, $options, $renderer)
            ->will($this->returnValue('MENU'));
        $this->assertEquals('MENU', $this->menuExtension->render($menu, $options, $renderer));
    }

    protected function assertGetMenuString($menu, $path, $options)
    {
        $menuInstance = $this->getMockBuilder('Knp\Menu\ItemInterface')
            ->setMethods(array('getChild', 'getExtra'))
            ->getMockForAbstractClass();
        $menuInstance->expects($this->once())
            ->method('getChild')
            ->with($path)
            ->will($this->returnSelf());
        $this->provider->expects($this->once())
            ->method('get')
            ->with($menu, $options)
            ->will($this->returnValue($menuInstance));
        return $menuInstance;
    }
}
