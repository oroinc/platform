<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Twig;

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
            ->setMethods('getChild', 'getExtra')
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
