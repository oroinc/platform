<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\UIBundle\Twig\TabExtension;

class TabExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $menuExtension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /**
     * @var TabExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $environment;

    /**
     * Set up test environment
     */
    public function setUp()
    {
        $this->menuExtension = $this
            ->getMockBuilder('Oro\Bundle\NavigationBundle\Twig\MenuExtension')
            ->disableOriginalConstructor()
            ->setMethods(['getMenu'])
            ->getMock();

        $this->router = $this
            ->getMockBuilder('Symfony\Component\Routing\RouterInterface')
            ->getMock();

        $this->extension = new TabExtension($this->menuExtension, $this->router);

        $this->environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testTabPanel()
    {
        $this->environment
            ->expects($this->once())
            ->method('render');

        $this->extension->tabPanel($this->environment, []);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\InvalidArgumentException
     * @expectedExceptionMessage Extra parameter "widgetRoute" should be defined for
     */
    public function testMenuTabPanelWithoutAnyParameters()
    {
        $child = $this->createMenuItem();
        $parent = $this->createMenuItem($child);

        $this->menuExtension
            ->expects($this->once())
            ->method('getMenu')
            ->will($this->returnValue($parent));

        $this->environment
            ->expects($this->never())
            ->method('render');

        $this->extension->menuTabPanel($this->environment, []);
    }

    public function testMenuTabPanel()
    {
        $child = $this->createMenuItem(null, ['uri' => 'test']);
        $parent = $this->createMenuItem($child);

        $this->menuExtension
            ->expects($this->once())
            ->method('getMenu')
            ->will($this->returnValue($parent));

        $this->environment
            ->expects($this->once())
            ->method('render');

        $this->extension->menuTabPanel($this->environment, []);
    }

    /**
     * @dataProvider menuProvider
     */
    public function testGetTabs($options, $tab, $tabOptions)
    {
        $child = $this->createMenuItem(null, $options);
        $parent = $this->createMenuItem($child);

        $this->menuExtension
            ->expects($this->once())
            ->method('getMenu')
            ->will($this->returnValue($parent));

        $this->router
            ->expects($this->any())
            ->method('generate')
            ->will(
                $this->returnCallback(
                    function ($route, $options) {
                        return $route . '?' . http_build_query($options);
                    }
                )
            );

        $result = $this->extension->getTabs('menu', $tabOptions);
        $this->assertEquals([$tab], $result);
    }

    public function menuProvider()
    {
        return [
            'uri' => [
                'options' => [
                    'name' => 'item',
                    'uri' => 'test',
                ],
                'tab' => [
                    'alias' => 'item',
                    'label' => null,
                    'widgetType' => TabExtension::DEFAULT_WIDGET_TYPE,
                    'url' => 'test'
                ],
                'tabOptions' => []
            ],
            'route' => [
                'options' => [
                    'name' => 'item',
                    'widgetRoute' => 'route',
                    'widgetRouteParameters' => ['type' => 'code'],
                ],
                'tab' => [
                    'alias' => 'item',
                    'label' => null,
                    'widgetType' => TabExtension::DEFAULT_WIDGET_TYPE,
                    'url' => 'route?' . http_build_query(['type' => 'code'])
                ],
                'tabOptions' => []
            ],
            'routeMap' => [
                'options' => [
                    'name' => 'item',
                    'widgetRoute' => 'route',
                    'widgetRouteParameters' => ['type' => 'code'],
                    'widgetRouteParametersMap' => ['type' => 'type2'],
                ],
                'tab' => [
                    'alias' => 'item',
                    'label' => null,
                    'widgetType' => TabExtension::DEFAULT_WIDGET_TYPE,
                    'url' => 'route?' . http_build_query(['type' => 'test']),
                ],
                'tabOptions' => ['type2' => 'test']
            ]
        ];
    }

    public function testName()
    {
        $this->assertEquals('oro_ui.tab_panel', $this->extension->getName());
    }

    public function testGetFunctions()
    {
        $this->assertArrayHasKey('menuTabPanel', $this->extension->getFunctions());
        $this->assertArrayHasKey('tabPanel', $this->extension->getFunctions());
    }

    protected function createMenuItem($child = null, $options = [])
    {
        $menuItem = $this
            ->getMockBuilder('Knp\Menu\MenuItem')
            ->disableOriginalConstructor()
            ->setMethods(['getChildren', 'getUri', 'getName', 'getExtra'])
            ->getMock();

        if ($child) {
            $menuItem
                ->expects($this->once())
                ->method('getChildren')
                ->will($this->returnValue([$child]));
        }

        if (isset($options['uri'])) {
            $menuItem
                ->expects($this->atLeastOnce())
                ->method('getUri')
                ->will($this->returnValue($options['uri']));
        }

        if (isset($options['name'])) {
            $menuItem
                ->expects($this->once())
                ->method('getName')
                ->will($this->returnValue($options['name']));
        }

        $menuItem
            ->expects($this->any())
            ->method('getExtra')
            ->will(
                $this->returnCallback(
                    function ($key, $default) use ($options) {
                        return isset($options[$key]) ? $options[$key] : $default;
                    }
                )
            );

        return $menuItem;
    }
}
