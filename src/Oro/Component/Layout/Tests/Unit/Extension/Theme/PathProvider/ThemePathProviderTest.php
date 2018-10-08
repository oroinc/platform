<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\PathProvider;

use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Extension\Theme\PathProvider\ThemePathProvider;
use Oro\Component\Layout\LayoutContext;

class ThemePathProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ThemeManager|\PHPUnit\Framework\MockObject\MockObject */
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
     * @param string|null $pageTemplateKey
     */
    public function testGetPaths(array $expectedResults, $theme, $route, $action, $pageTemplateKey)
    {
        $context = new LayoutContext();
        $context->set('theme', $theme);
        $context->set('route_name', $route);
        $context->set('action', $action);
        $context->set('page_template', $pageTemplateKey);

        $pageTemplate = $pageTemplateKey ? new PageTemplate('', $pageTemplateKey, $route) : null;
        $this->setUpThemeManager(
            [
                'black' => $this->getThemeMock('black', 'base', $pageTemplate),
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
                'action'          => null,
                'page_template'   => null,
            ],
            [
                'expectedResults' => [
                    'base'
                ],
                'theme'           => 'base',
                'route'           => null,
                'action'          => null,
                'page_template'   => null,
            ],
            [
                'expectedResults' => [
                    'base',
                    'base/route'
                ],
                'theme'           => 'base',
                'route'           => 'route',
                'action'          => null,
                'page_template'   => null,
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
                'action'          => null,
                'page_template'   => null,
            ],
            [
                'expectedResults' => [
                    'base',
                    'base/index',
                    'base/route'
                ],
                'theme'           => 'base',
                'route'           => 'route',
                'action'          => 'index',
                'page_template'   => null,
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
                'action'          => 'index',
                'page_template'   => null,
            ],
            [
                'expectedResults' => [
                    'base',
                    'black',
                    'base/index',
                    'black/index',
                    'base/route',
                    'black/route',
                    'base/route/page_template/sample_page_template',
                    'black/route/page_template/sample_page_template'
                ],
                'theme'           => 'black',
                'route'           => 'route',
                'action'          => 'index',
                'page_template'   => 'sample_page_template',
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
     * @param null|string  $directory
     * @param null|string  $parent
     * @param PageTemplate $pageTemplate
     *
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getThemeMock($directory = null, $parent = null, PageTemplate $pageTemplate = null)
    {
        $theme = $this->createMock('Oro\Component\Layout\Extension\Theme\Model\Theme');
        $theme->expects($this->any())->method('getParentTheme')->willReturn($parent);
        $theme->expects($this->any())->method('getDirectory')->willReturn($directory);
        $theme->expects($this->any())->method('getPageTemplate')
            ->willReturn($pageTemplate);

        return $theme;
    }
}
