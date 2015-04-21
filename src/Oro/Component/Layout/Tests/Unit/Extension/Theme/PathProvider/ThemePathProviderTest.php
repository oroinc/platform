<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\PathProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Extension\Theme\PathProvider\ThemePathProvider;

class ThemePathProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ThemeManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $themeManager;

    /** @var ThemePathProvider */
    protected $provider;

    protected function setUp()
    {
        $this->themeManager = $this->getMockBuilder('Oro\Component\Layout\Extension\Theme\Model\ThemeManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new ThemePathProvider($this->themeManager);
    }

    /**
     * @dataProvider pathsDataProvider
     *
     * @param array       $expectedResults
     * @param string|null $theme
     * @param string|null $route
     * @param string|null $action
     */
    public function testGetPaths(array $expectedResults, $theme, $route, $action)
    {
        $context = new LayoutContext();
        $context->set('theme', $theme);
        $context->set('route_name', $route);
        $context->set('action', $action);
        $this->setUpThemeManager(
            [
                'black' => $this->getThemeMock('black', 'base'),
                'base'  => $this->getThemeMock('base')
            ]
        );
        $this->provider->setContext($context);
        $this->assertSame($expectedResults, $this->provider->getPaths([]));
    }

    /**
     * @return array
     */
    public function pathsDataProvider()
    {
        return [
            [
                'expectedResults' => [],
                'theme'           => null,
                'route'           => null,
                'action'          => null
            ],
            [
                'expectedResults' => [
                    'base'
                ],
                'theme'           => 'base',
                'route'           => null,
                'action'          => null
            ],
            [
                'expectedResults' => [
                    'base',
                    'base/route'
                ],
                'theme'           => 'base',
                'route'           => 'route',
                'action'          => null
            ],
            [
                'expectedResults' => [
                    'base',
                    'black',
                    'base/route',
                    'black/route'
                ],
                'theme'           => 'black',
                'route'           => 'route',
                'action'          => null
            ],
            [
                'expectedResults' => [
                    'base',
                    'base/index',
                    'base/route'
                ],
                'theme'           => 'base',
                'route'           => 'route',
                'action'          => 'index'
            ],
            [
                'expectedResults' => [
                    'base',
                    'black',
                    'base/index',
                    'black/index',
                    'base/route',
                    'black/route'
                ],
                'theme'           => 'black',
                'route'           => 'route',
                'action'          => 'index'
            ]
        ];
    }

    /**
     * @param array $themes
     */
    protected function setUpThemeManager(array $themes)
    {
        $map = [];

        foreach ($themes as $themeName => $theme) {
            $map[] = [$themeName, $theme];
        }

        $this->themeManager->expects($this->any())->method('getTheme')->willReturnMap($map);
    }

    /**
     * @param null|string $parent
     * @param null|string $directory
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getThemeMock($directory = null, $parent = null)
    {
        $theme = $this->getMock('Oro\Component\Layout\Extension\Theme\Model\Theme', [], [], '', false);
        $theme->expects($this->any())->method('getParentTheme')->willReturn($parent);
        $theme->expects($this->any())->method('getDirectory')->willReturn($directory);

        return $theme;
    }
}
