<?php

namespace Oro\Component\Layout\Tests\Unit\Extension\Theme\PathProvider;

use Oro\Component\Layout\Extension\Theme\Model\PageTemplate;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Oro\Component\Layout\Extension\Theme\PathProvider\ThemePathProvider;
use Oro\Component\Layout\LayoutContext;

class ThemePathProviderTest extends \PHPUnit\Framework\TestCase
{
    private ThemeManager|\PHPUnit\Framework\MockObject\MockObject $themeManager;

    private ThemePathProvider $provider;

    protected function setUp(): void
    {
        $this->themeManager = $this->createMock(ThemeManager::class);

        $this->provider = new ThemePathProvider($this->themeManager);
    }

    /**
     * @dataProvider pathsDataProvider
     */
    public function testGetPaths(array $expectedResults, ?string $theme, ?string $route, ?string $action): void
    {
        $context = new LayoutContext();
        $context->set('theme', $theme);
        $context->set('route_name', $route);
        $context->set('action', $action);

        $this->themeManager
            ->expects(self::any())
            ->method('getThemesHierarchy')
            ->willReturnMap([
                ['base', [new Theme('base')]],
                ['black', [new Theme('base'), new Theme('black', 'base')]],
            ]);

        $this->provider->setContext($context);
        self::assertSame($expectedResults, $this->provider->getPaths([]));
    }

    public function pathsDataProvider(): array
    {
        return [
            [
                'expectedResults' => [],
                'theme' => null,
                'route' => null,
                'action' => null,
                'page_template' => null,
            ],
            [
                'expectedResults' => [
                    'base',
                ],
                'theme' => 'base',
                'route' => null,
                'action' => null,
                'page_template' => null,
            ],
            [
                'expectedResults' => [
                    'base',
                    'base/route',
                ],
                'theme' => 'base',
                'route' => 'route',
                'action' => null,
                'page_template' => null,
            ],
            [
                'expectedResults' => [
                    'base',
                    'black',
                    'base/route',
                    'black/route',
                ],
                'theme' => 'black',
                'route' => 'route',
                'action' => null,
                'page_template' => null,
            ],
            [
                'expectedResults' => [
                    'base',
                    'base/index',
                    'base/route',
                ],
                'theme' => 'base',
                'route' => 'route',
                'action' => 'index',
                'page_template' => null,
            ],
            [
                'expectedResults' => [
                    'base',
                    'black',
                    'base/index',
                    'black/index',
                    'base/route',
                    'black/route',
                ],
                'theme' => 'black',
                'route' => 'route',
                'action' => 'index',
                'page_template' => null,
            ],
        ];
    }

    public function testGetPathsWhenPageTemplate(): void
    {
        $themeName = 'black';
        $parentName = 'base';
        $routeName = 'route';
        $pageTemplate = 'sample_page_template';

        $context = new LayoutContext();
        $context->set('theme', $themeName);
        $context->set('route_name', $routeName);
        $context->set('action', 'index');
        $context->set('page_template', $pageTemplate);

        $pageTemplate = new PageTemplate('', $pageTemplate, $routeName);
        $theme = (new Theme($themeName, $parentName))
            ->addPageTemplate($pageTemplate);

        $this->themeManager
            ->expects(self::any())
            ->method('getThemesHierarchy')
            ->with($themeName)
            ->willReturn([new Theme($parentName), $theme]);

        $this->provider->setContext($context);
        self::assertSame(
            [
                $parentName,
                $themeName,
                'base/index',
                'black/index',
                'base/route',
                'black/route',
                'base/route/page_template/sample_page_template',
                'black/route/page_template/sample_page_template',
            ],
            $this->provider->getPaths([])
        );
    }
}
