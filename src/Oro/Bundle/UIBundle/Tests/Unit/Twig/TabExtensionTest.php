<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Twig;

use Knp\Menu\MenuItem;
use Oro\Bundle\NavigationBundle\Twig\MenuExtension;
use Oro\Bundle\UIBundle\Twig\TabExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Exception\InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class TabExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $environment;

    /** @var MenuExtension|\PHPUnit\Framework\MockObject\MockObject */
    private $menuExtension;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $router;

    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var TabExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);
        $this->menuExtension = $this->createMock(MenuExtension::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $container = self::getContainerBuilder()
            ->add('oro_menu.twig.extension', $this->menuExtension)
            ->add(RouterInterface::class, $this->router)
            ->add(AuthorizationCheckerInterface::class, $this->authorizationChecker)
            ->add(TranslatorInterface::class, $this->translator)
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
            self::callTwigFunction($this->extension, 'tabPanel', [$this->environment, []])
        );
        self::assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'tabPanel', [$this->environment, [], []])
        );
    }

    public function testMenuTabPanelWithoutAnyParameters()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Extra parameter "widgetRoute" should be defined for');

        $child = $this->createMenuItem();
        $child->expects($this->once())
            ->method('isDisplayed')
            ->willReturn(true);

        $parent = $this->createMenuItem($child);

        $this->menuExtension->expects($this->once())
            ->method('getMenu')
            ->willReturn($parent);

        $this->environment->expects($this->never())
            ->method('render');

        self::callTwigFunction($this->extension, 'menuTabPanel', [$this->environment, []]);
    }

    public function testMenuTabPanel()
    {
        $expected = 'test';
        $child = $this->createMenuItem(null, ['uri' => 'test', 'widgetAcl' => 'testAcl']);
        $child->expects($this->once())
            ->method('isDisplayed')
            ->willReturn(true);

        $acl = [['testAcl', null, true]];
        $parent = $this->createMenuItem($child);

        $this->menuExtension->expects($this->once())
            ->method('getMenu')
            ->willReturn($parent);

        $this->environment->expects($this->once())
            ->method('render')
            ->willReturn($expected);

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnMap($acl);

        self::assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'menuTabPanel', [$this->environment, []])
        );
    }

    /**
     * @dataProvider menuProvider
     */
    public function testGetTabs(array $options, ?array $tab, array $tabOptions, array $acl, $isDisplayed = true)
    {
        $child = $this->createMenuItem(null, $options);
        $child->expects($this->once())
            ->method('isDisplayed')
            ->willReturn($isDisplayed);

        $parent = $this->createMenuItem($child);

        $this->menuExtension->expects($this->once())
            ->method('getMenu')
            ->willReturn($parent);

        $this->router->expects($this->any())
            ->method('generate')
            ->willReturnCallback(function ($route, $options) {
                return $route . '?' . http_build_query($options);
            });

        $this->authorizationChecker->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function ($aclResource) use ($acl) {
                return $acl[$aclResource];
            });

        if (empty($options['label'])) {
            $this->translator->expects($this->never())
                ->method('trans');
        } else {
            $this->translator->expects($this->once())
                ->method('trans')
                ->willReturnArgument(0);
        }

        $result = $this->extension->getTabs('menu', $tabOptions);

        $this->assertEquals($tab ? [$tab] : [], $result);
    }

    public function menuProvider(): array
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
                    'widgetType' => 'block',
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
                    'widgetType' => 'block',
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
                    'widgetType' => 'block',
                    'url'        => 'route?' . http_build_query(['type' => 'test']),
                ],
                'tabOptions' => ['type2' => 'test'],
                'acl'        => [
                    'testAcl' => true
                ]
            ],
            'accessDenied'              => [
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

    /**
     * @return MenuItem|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createMenuItem(MenuItem $child = null, array $options = []): MenuItem
    {
        $menuItem = $this->createMock(MenuItem::class);
        if ($child) {
            $menuItem->expects($this->once())
                ->method('getChildren')
                ->willReturn([$child]);
        }
        if (isset($options['uri'])) {
            $menuItem->expects($this->atLeastOnce())
                ->method('getUri')
                ->willReturn($options['uri']);
        }
        if (isset($options['name'])) {
            $menuItem->expects($this->any())
                ->method('getName')
                ->willReturn($options['name']);
        }
        if (isset($options['label'])) {
            $menuItem->expects($this->any())
                ->method('getLabel')
                ->willReturn($options['label']);
        }
        $menuItem->expects($this->any())
            ->method('getExtra')
            ->willReturnCallback(function ($key, $default) use ($options) {
                return $options[$key] ?? $default;
            });

        return $menuItem;
    }
}
