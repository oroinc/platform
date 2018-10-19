<?php

namespace Oro\Bundle\SidebarBundle\Tests\Unit\Model;

use Oro\Bundle\SidebarBundle\Model\WidgetDefinitionRegistry;

class WidgetDefinitionRegistryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider configurationDataProvider
     * @param array $definitions
     * @param string $placement
     * @param array $expected
     */
    public function testGetWidgetDefinitionsByPlacement(array $definitions, $placement, array $expected)
    {
        $registry = new WidgetDefinitionRegistry($definitions);
        self::assertEquals($expected, $registry->getWidgetDefinitionsByPlacement($placement));

        $additionalDefinition = ['last' => ['icon' => 'icon.png']];
        $registry->setWidgetDefinitions($additionalDefinition);
        self::assertEquals(
            array_merge($definitions, $additionalDefinition),
            $registry->getWidgetDefinitions()
        );
    }

    /**
     * @return array
     */
    public function configurationDataProvider()
    {
        return [
            'empty' => [
                [],
                'left',
                []
            ],
            'full left' => [
                [
                    'foo' => [
                        'title' => 'Foo',
                        'icon' => 'foo.ico',
                        'module' => 'widget/foo',
                        'placement' => 'left'
                    ],
                    'bar' => [
                        'title' => 'Bar',
                        'icon' => 'bar.ico',
                        'module' => 'widget/bar',
                        'placement' => 'both'
                    ],
                    'bar2' => [
                        'title' => 'Bar2',
                        'icon' => 'bar2.ico',
                        'module' => 'widget/bar2',
                        'placement' => 'right'
                    ]
                ],
                'left',
                [
                    'foo' => [
                        'title' => 'Foo',
                        'icon' => 'foo.ico',
                        'module' => 'widget/foo',
                        'placement' => 'left'
                    ],
                    'bar' => [
                        'title' => 'Bar',
                        'icon' => 'bar.ico',
                        'module' => 'widget/bar',
                        'placement' => 'both'
                    ]
                ]
            ],
            'full right' => [
                [
                    'foo' => [
                        'title' => 'Foo',
                        'icon' => 'foo.ico',
                        'module' => 'widget/foo',
                        'placement' => 'left'
                    ],
                    'bar' => [
                        'title' => 'Bar',
                        'icon' => 'bar.ico',
                        'module' => 'widget/bar',
                        'placement' => 'both'
                    ],
                    'bar2' => [
                        'title' => 'Bar2',
                        'icon' => 'bar2.ico',
                        'module' => 'widget/bar2',
                        'placement' => 'right'
                    ]
                ],
                'right',
                [
                    'bar' => [
                        'title' => 'Bar',
                        'icon' => 'bar.ico',
                        'module' => 'widget/bar',
                        'placement' => 'both'
                    ],
                    'bar2' => [
                        'title' => 'Bar2',
                        'icon' => 'bar2.ico',
                        'module' => 'widget/bar2',
                        'placement' => 'right'
                    ]
                ]
            ]
        ];
    }
}
