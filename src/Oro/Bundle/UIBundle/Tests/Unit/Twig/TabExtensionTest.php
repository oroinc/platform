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
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * Set up test environment
     */
    protected function setUp()
    {
        $this->menuExtension = $this
            ->getMockBuilder('Oro\Bundle\NavigationBundle\Twig\MenuExtension')
            ->disableOriginalConstructor()
            ->setMethods(['getMenu'])
            ->getMock();

        $this->router = $this
            ->getMockBuilder('Symfony\Component\Routing\RouterInterface')
            ->getMock();

        $this->securityFacade = $this
            ->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->extension = new TabExtension(
            $this->menuExtension,
            $this->router,
            $this->securityFacade,
            $this->translator
        );

        $this->environment = $this->getMockBuilder('\Twig_Environment')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testTabPanel()
    {
        $this->environment
            ->expects($this->exactly(2))
            ->method('render');

        $this->extension->tabPanel($this->environment, $tabs = []);
        $this->extension->tabPanel($this->environment, $tabs = [], $options = []);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\InvalidArgumentException
     * @expectedExceptionMessage Extra parameter "widgetRoute" should be defined for
     */
    public function testMenuTabPanelWithoutAnyParameters()
    {
        $child = $this->createMenuItem();
        $child->expects($this->once())
            ->method('isDisplayed')
            ->will($this->returnValue(true));

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
        $child  = $this->createMenuItem(null, ['uri' => 'test', 'widgetAcl' => 'testAcl']);
        $child
            ->expects($this->once())
            ->method('isDisplayed')
            ->will($this->returnValue(true));

        $acl    = [['testAcl', null, true]];
        $parent = $this->createMenuItem($child);

        $this->menuExtension
            ->expects($this->once())
            ->method('getMenu')
            ->will($this->returnValue($parent));

        $this->environment
            ->expects($this->once())
            ->method('render');

        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->will(
                $this->returnValueMap($acl)
            );

        $this->extension->menuTabPanel($this->environment, []);
    }

    /**
     * @dataProvider menuProvider
     */
    public function testGetTabs($options, $tab, $tabOptions, $acl, $isDisplayed = true)
    {
        $child = $this->createMenuItem(null, $options);
        $child
            ->expects($this->once())
            ->method('isDisplayed')
            ->will($this->returnValue($isDisplayed));

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

        $this->securityFacade->expects($this->any())
            ->method('isGranted')
            ->will(
                $this->returnCallback(
                    function ($aclResource) use ($acl) {
                        return $acl[$aclResource];
                    }
                )
            );

        if (empty($options['label'])) {
            $this->translator->expects($this->never())
                ->method('trans');
        } else {
            $this->translator->expects($this->once())
                ->method('trans')
                ->will($this->returnArgument(0));
        }

        $result = $this->extension->getTabs('menu', $tabOptions);

        $this->assertEquals($tab ? [$tab] : [], $result);
    }

    public function menuProvider()
    {
        return [
            'uri and label'             => [
                'options'    => [
                    'name'      => 'item',
                    'uri'       => 'test',
                    'label'     => 'testLabel',
                    'widgetAcl' => 'testAcl',
                ],
                'tab'        => [
                    'alias'      => 'item',
                    'label'      => 'testLabel',
                    'widgetType' => TabExtension::DEFAULT_WIDGET_TYPE,
                    'url'        => 'test'
                ],
                'tabOptions' => [],
                'acl'        => [
                    'testAcl' => true
                ]
            ],
            'route'                     => [
                'options'    => [
                    'name'                  => 'item',
                    'widgetRoute'           => 'route',
                    'widgetAcl'             => 'testAcl',
                    'widgetRouteParameters' => ['type' => 'code'],
                ],
                'tab'        => [
                    'alias'      => 'item',
                    'label'      => null,
                    'widgetType' => TabExtension::DEFAULT_WIDGET_TYPE,
                    'url'        => 'route?' . http_build_query(['type' => 'code'])
                ],
                'tabOptions' => [],
                'acl'        => [
                    'testAcl' => true
                ]
            ],
            'routeMap'                  => [
                'options'    => [
                    'name'                     => 'item',
                    'widgetRoute'              => 'route',
                    'widgetAcl'                => 'testAcl',
                    'widgetRouteParameters'    => ['type' => 'code'],
                    'widgetRouteParametersMap' => ['type' => 'type2'],
                ],
                'tab'        => [
                    'alias'      => 'item',
                    'label'      => null,
                    'widgetType' => TabExtension::DEFAULT_WIDGET_TYPE,
                    'url'        => 'route?' . http_build_query(['type' => 'test']),
                ],
                'tabOptions' => ['type2' => 'test'],
                'acl'        => [
                    'testAcl' => true
                ]
            ],
            'accessDenide'              => [
                'options'    => [
                    'name'      => 'item',
                    'uri'       => 'test',
                    'widgetAcl' => 'testAcl',
                ],
                'tab'        => null,
                'tabOptions' => [],
                'acl'        => [
                    'testAcl' => false
                ]
            ],
            'routeMap does not display' => [
                'options'    => [
                    'name'                     => 'item',
                    'widgetRoute'              => 'route',
                    'widgetAcl'                => 'testAcl',
                    'widgetRouteParameters'    => ['type' => 'code'],
                    'widgetRouteParametersMap' => ['type' => 'type2'],
                ],
                'tab'        => [],
                'tabOptions' => ['type2' => 'test'],
                'acl'        => [
                    'testAcl' => true
                ],
                false
            ],
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
            ->setMethods(['getChildren', 'getUri', 'getName', 'getLabel', 'getExtra', 'isDisplayed'])
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
                ->expects($this->any())
                ->method('getName')
                ->will($this->returnValue($options['name']));
        }

        if (isset($options['label'])) {
            $menuItem
                ->expects($this->any())
                ->method('getLabel')
                ->will($this->returnValue($options['label']));
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
