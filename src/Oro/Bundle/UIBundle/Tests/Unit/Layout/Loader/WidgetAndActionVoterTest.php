<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Loader;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\UIBundle\Layout\Loader\WidgetAndActionVoter;
use Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Loader\AbstractPathVoterTestCase;

/**
 * @property WidgetAndActionVoter voter
 */
class WidgetAndActionVoterTest extends AbstractPathVoterTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->voter = new WidgetAndActionVoter($this->themeManager);
    }

    /**
     * @dataProvider pathsDataProvider
     *
     * @param array       $paths
     * @param array       $expectedToPass
     * @param string|null $theme
     * @param string|null $route
     * @param string|null $widget
     * @param string|null $action
     */
    public function testThemeHierarchyWithRoutePassed(
        array $paths,
        array $expectedToPass,
        $theme,
        $route,
        $widget,
        $action
    ) {
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
        $this->voter->setContext($context);

        $passed = [];
        foreach ($paths as $path) {
            if ($this->voter->vote($path, '')) {
                $passed [] = $path;
            }
        }

        $this->assertSame($expectedToPass, $passed);
    }

    /**
     * @return array
     */
    public function pathsDataProvider()
    {
        $paths = [
            ['base'],
            ['base', 'dialog'],
            ['base', 'route'],
            ['base', 'route', 'dialog'],
            ['base', 'index'],
            ['base', 'index', 'dialog'],
            ['black'],
            ['black', 'dialog'],
            ['black', 'route', 'index'],
            ['black', 'route', 'dialog'],
            ['black', 'index'],
        ];

        return [
            [
                '$paths'          => $paths,
                '$expectedToPass' => [
                    ['base', 'dialog'],
                    ['base', 'route', 'dialog'],
                    ['base', 'index'],
                    ['base', 'index', 'dialog']
                ],
                '$theme'          => 'base',
                '$route'          => 'route',
                '$widget'         => 'dialog',
                '$action'         => 'index',
            ],
            [
                '$paths'          => $paths,
                '$expectedToPass' => [
                    ['base', 'dialog'],
                    ['base', 'route', 'dialog'],
                    ['black', 'dialog'],
                    ['black', 'route', 'dialog']
                ],
                '$theme'          => 'black',
                '$route'          => 'route',
                '$widget'         => 'dialog',
                '$action'         => null,
            ],
            [
                '$paths'          => $paths,
                '$expectedToPass' => [],
                '$theme'          => 'black',
                '$route'          => 'route',
                '$widget'         => null,
                '$action'         => null,
            ]
        ];
    }
}
