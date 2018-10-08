<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Oro\Bundle\NavigationBundle\Twig\MenuExtension;
use Oro\Bundle\UIBundle\Twig\TabExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class TabExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $environment;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $menuExtension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var TabExtension */
    protected $extension;

    protected function setUp()
    {
        $this->environment = $this->createMock(\Twig_Environment::class);
        $this->menuExtension = $this->getMockBuilder(MenuExtension::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMenu'])
            ->getMock();
        $this->router = $this->createMock(RouterInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_menu.twig.extension', $this->menuExtension)
            ->add('router', $this->router)
            ->add('security.authorization_checker', $this->authorizationChecker)
            ->add('translator', $this->translator)
            ->getContainer($this);

        $this->extension = new TabExtension($container);
    }

    public function testTabPanel()
    {
        $expected = 'test';

        $this->environment->expects($this->exactly(2))
            ->method('render')
            ->willReturn($expected);

        self::assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'tabPanel', [$this->environment, $tabs = []])
        );
        self::assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'tabPanel', [$this->environment, $tabs = [], $options = []])
        );
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

        self::callTwigFunction($this->extension, 'menuTabPanel', [$this->environment, []]);
    }

    public function testMenuTabPanel()
    {
        $expected = 'test';
        $child = $this->createMenuItem(null, ['uri' => 'test', 'widgetAcl' => 'testAcl']);
        $child->expects($this->once())
            ->method('isDisplayed')
            ->will($this->returnValue(true));

        $acl = [['testAcl', null, true]];
        $parent = $this->createMenuItem($child);

        $this->menuExtension->expects($this->once())
            ->method('getMenu')
            ->will($this->returnValue($parent));

        $this->environment->expects($this->once())
            ->method('render')
            ->willReturn($expected);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->will(
                $this->returnValueMap($acl)
            );

        self::assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'menuTabPanel', [$this->environment, []])
        );
    }

    /**
     * @dataProvider menuProvider
     */
    public function testGetTabs($options, $tab, $tabOptions, $acl, $isDisplayed = true)
    {
        $child = $this->createMenuItem(null, $options);
        $child->expects($this->once())
            ->method('isDisplayed')
            ->will($this->returnValue($isDisplayed));

        $parent = $this->createMenuItem($child);

        $this->menuExtension->expects($this->once())
            ->method('getMenu')
            ->will($this->returnValue($parent));

        $this->router->expects($this->any())
            ->method('generate')
            ->will(
                $this->returnCallback(
                    function ($route, $options) {
                        return $route . '?' . http_build_query($options);
                    }
                )
            );

        $this->authorizationChecker->expects($this->any())
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
