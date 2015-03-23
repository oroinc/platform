<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Loader;

use Oro\Component\Layout\LayoutContext;

use Oro\Bundle\UIBundle\Layout\Loader\WidgetPathProvider;

/**
 * @property WidgetPathProvider provider
 */
class WidgetPathProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var WidgetPathProvider */
    protected $provider;

    protected function setUp()
    {
        $this->provider = new WidgetPathProvider();
    }

    /**
     * @dataProvider pathsDataProvider
     *
     * @param string[]    $existingPaths
     * @param string|null $widget
     * @param string[]    $expectedPaths
     */
    public function testGetPaths($existingPaths, $widget, $expectedPaths)
    {
        $context = new LayoutContext();
        $context->set('widget_container', $widget);
        $this->provider->setContext($context);
        $this->assertSame($expectedPaths, $this->provider->getPaths($existingPaths));
    }

    /**
     * @return array
     */
    public function pathsDataProvider()
    {
        return [
            [
                'existingPaths' => [],
                'widget'        => null,
                'expectedPaths' => []
            ],
            [
                'existingPaths' => [],
                'widget'        => 'dialog',
                'expectedPaths' => []
            ],
            [
                'existingPaths' => [
                    'base',
                    'base/action',
                    'base/route'
                ],
                'widget'        => 'dialog',
                'expectedPaths' => [
                    'base',
                    'base/action',
                    'base/action/dialog',
                    'base/route',
                    'base/route/dialog'
                ]
            ],
            [
                'existingPaths' => [
                    'base',
                    'black',
                    'base/action',
                    'black/action',
                    'base/route',
                    'black/route'
                ],
                'widget'        => 'dialog',
                'expectedPaths' => [
                    'base',
                    'black',
                    'base/action',
                    'base/action/dialog',
                    'black/action',
                    'black/action/dialog',
                    'base/route',
                    'base/route/dialog',
                    'black/route',
                    'black/route/dialog'
                ]
            ]
        ];
    }
}
