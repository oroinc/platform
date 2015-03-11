<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Loader;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\UIBundle\Layout\Loader\WidgetAndActionPathProvider;
use Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Loader\AbstractPathProviderTestCase;

/**
 * @property WidgetAndActionPathProvider provider
 */
class WidgetAndActionPathProviderTest extends AbstractPathProviderTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->provider = new WidgetAndActionPathProvider($this->themeManager);
    }

    /**
     * @dataProvider pathsDataProvider
     *
     * @param array       $expectedResults
     * @param string|null $theme
     * @param string|null $route
     * @param string|null $widget
     * @param string|null $action
     */
    public function testThemeHierarchyWithRoutePassed(array $expectedResults, $theme, $route, $widget, $action)
    {
        $context = new LayoutContext();
        $context->set('theme', $theme);
        $context->set('route_name', $route);
        $context->set('widget_container', $widget);
        $context->set('action', $action);
        $this->setUpThemeManager(
            [
                'black' => $this->getThemeMock('black', 'base'),
                'base'  => $this->getThemeMock('base')
            ]
        );
        $this->provider->setContext($context);
        $this->assertSame($expectedResults, $this->provider->getPaths());
    }

    /**
     * @return array
     */
    public function pathsDataProvider()
    {
        return [
            [
                '$expectedResults' => [
                    'base/index',
                    'base/index/dialog',
                    'base/dialog',
                    'base/route/index',
                    'base/route/index/dialog',
                    'base/route/dialog',
                ],
                '$theme'           => 'base',
                '$route'           => 'route',
                '$widget'          => 'dialog',
                '$action'          => 'index',
            ],
            [
                '$expectedResults' => [
                    'base/dialog',
                    'base/route/dialog',
                    'black/dialog',
                    'black/route/dialog',
                ],
                '$theme'           => 'black',
                '$route'           => 'route',
                '$widget'          => 'dialog',
                '$action'          => null,
            ],
            [
                '$expectedResults' => [],
                '$theme'           => 'black',
                '$route'           => 'route',
                '$widget'          => null,
                '$action'          => null,
            ]
        ];
    }
}
