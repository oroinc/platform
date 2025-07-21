<?php

namespace Oro\Bundle\UIBundle\Tests\Unit\Layout\Extension\Theme;

use Oro\Bundle\UIBundle\Layout\Extension\Theme\WidgetPathProvider;
use Oro\Component\Layout\LayoutContext;
use PHPUnit\Framework\TestCase;

class WidgetPathProviderTest extends TestCase
{
    private WidgetPathProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new WidgetPathProvider();
    }

    /**
     * @dataProvider pathsDataProvider
     */
    public function testGetPaths(array $existingPaths, ?string $widget, array $expectedPaths): void
    {
        $context = new LayoutContext();
        $context->set('widget_container', $widget);
        $this->provider->setContext($context);
        $this->assertSame($expectedPaths, $this->provider->getPaths($existingPaths));
    }

    public function pathsDataProvider(): array
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
                'widget'        => null,
                'expectedPaths' => [
                    'base',
                    'base/page',
                    'base/action',
                    'base/action/page',
                    'base/route',
                    'base/route/page'
                ]
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
                    'base/dialog',
                    'base/action',
                    'base/action/dialog',
                    'base/route',
                    'base/route/dialog'
                ]
            ]
        ];
    }
}
