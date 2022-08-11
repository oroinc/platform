<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ThemeConfigurationTest extends \PHPUnit\Framework\TestCase
{
    private function processConfiguration(array $config): array
    {
        return (new Processor())->processConfiguration(new ThemeConfiguration(), [$config]);
    }

    public function testProcessEmptyConfiguration(): void
    {
        $result = $this->processConfiguration([]);
        self::assertSame([], $result);
    }

    public function testProcessBaseConfiguration(): void
    {
        $themeConfig = [
            'label'              => 'test label',
            'description'        => 'test description',
            'parent'             => 'test_parent',
            'directory'          => 'test_directory',
            'groups'             => ['test group'],
            'icon'               => 'test.ico',
            'image_placeholders' => ['placeholder' => '/path/to/test.img'],
            'rtl_support'        => true,
            'logo'               => 'test_logo.jpg',
            'screenshot'         => 'test_screenshot.jpg',
            'extra_js_builds'    => []
        ];
        $result = $this->processConfiguration(['test_theme' => $themeConfig]);
        self::assertSame($themeConfig, $result['test_theme']);
    }

    public function testProcessAssets(): void
    {
        $themeConfig = [
            'label'  => 'test label',
            'config' => [
                'assets' => [
                    'css' => [
                        'inputs'  => ['input.scss'],
                        'output'  => 'output.css',
                        'filters' => ['test_filter'],
                        'auto_rtl_inputs' => ['bundles/test/**']
                    ]
                ]
            ]
        ];
        $result = $this->processConfiguration(['test_theme' => $themeConfig]);
        self::assertSame($themeConfig['config']['assets'], $result['test_theme']['config']['assets']);
    }

    public function testProcessImagesTypes(): void
    {
        $themeConfig = [
            'label'  => 'test label',
            'config' => [
                'images' => [
                    'types' => [
                        'test_type1' => [
                            'label' => 'Test Type 1'
                        ],
                        'test_type2' => [
                            'label'      => 'Test Type 2',
                            'max_number' => 1,
                            'dimensions' => ['test_dimension']
                        ]
                    ]
                ]
            ]
        ];
        $expected = $themeConfig;
        $expected['config']['images']['types']['test_type1']['max_number'] = null;
        $expected['config']['images']['types']['test_type1']['dimensions'] = [];
        $expected['config']['images']['dimensions'] = [];

        $result = $this->processConfiguration(['test_theme' => $themeConfig]);
        self::assertSame($expected['config']['images'], $result['test_theme']['config']['images']);
    }

    public function testProcessImagesDimensions(): void
    {
        $themeConfig = [
            'label'  => 'test label',
            'config' => [
                'images' => [
                    'dimensions' => [
                        'test_dimension1' => [
                            'width'  => null,
                            'height' => null
                        ],
                        'test_dimension2' => [
                            'width'  => 'auto',
                            'height' => 10
                        ],
                        'test_dimension3' => [
                            'width'  => 10,
                            'height' => 'auto'
                        ],
                        'test_dimension4' => [
                            'width'   => 10,
                            'height'  => 20,
                            'options' => [
                                'test_option' => 'val'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $expected = $themeConfig;
        $expected['config']['images']['dimensions']['test_dimension1']['options'] = [];
        $expected['config']['images']['dimensions']['test_dimension2']['options'] = [];
        $expected['config']['images']['dimensions']['test_dimension3']['options'] = [];
        $expected['config']['images']['types'] = [];

        $result = $this->processConfiguration(['test_theme' => $themeConfig]);
        self::assertSame($expected['config']['images'], $result['test_theme']['config']['images']);
    }

    public function testProcessImagesDimensionWithoutHeight(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The child config "height" under "themes.test_theme.config.images.dimensions.test_dimension1"'
            . ' must be configured.'
        );

        $themeConfig = [
            'label'  => 'test label',
            'config' => [
                'images' => [
                    'dimensions' => [
                        'test_dimension1' => [
                            'width' => null
                        ]
                    ]
                ]
            ]
        ];
        $this->processConfiguration(['test_theme' => $themeConfig]);
    }

    public function testProcessImagesDimensionWithoutWidth(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The child config "width" under "themes.test_theme.config.images.dimensions.test_dimension1"'
            . ' must be configured.'
        );

        $themeConfig = [
            'label'  => 'test label',
            'config' => [
                'images' => [
                    'dimensions' => [
                        'test_dimension1' => [
                            'height' => null
                        ]
                    ]
                ]
            ]
        ];
        $this->processConfiguration(['test_theme' => $themeConfig]);
    }

    public function testProcessPageTemplates(): void
    {
        $themeConfig = [
            'label'  => 'test label',
            'config' => [
                'page_templates' => [
                    'templates' => [
                        [
                            'route_name' => 'test_route1',
                            'key'        => 'test_key1',
                            'label'      => 'test label 1'
                        ],
                        [
                            'route_name' => 'test_route2',
                            'key'        => 'test_key2',
                            'label'      => 'test label 2',
                            'enabled'    => true
                        ],
                        [
                            'route_name'  => 'test_route3',
                            'key'         => 'test_key3',
                            'label'       => 'test label 3',
                            'description' => 'test description 3',
                            'screenshot'  => 'test_screenshot3.jpg',
                            'enabled'     => false
                        ]
                    ]
                ]
            ]
        ];
        $expected = $themeConfig;
        $expected['config']['page_templates']['templates'][0]['description'] = null;
        $expected['config']['page_templates']['templates'][0]['screenshot'] = null;
        $expected['config']['page_templates']['templates'][0]['enabled'] = null;
        $expected['config']['page_templates']['templates'][1]['description'] = null;
        $expected['config']['page_templates']['templates'][1]['screenshot'] = null;
        $expected['config']['page_templates']['titles'] = [];

        $result = $this->processConfiguration(['test_theme' => $themeConfig]);
        self::assertSame($expected['config']['page_templates'], $result['test_theme']['config']['page_templates']);
    }

    public function testProcessPageTemplatesTitles(): void
    {
        $themeConfig = [
            'label'  => 'test label',
            'config' => [
                'page_templates' => [
                    'titles' => ['test title']
                ]
            ]
        ];
        $expected = $themeConfig;
        $expected['config']['page_templates']['templates'] = [];

        $result = $this->processConfiguration(['test_theme' => $themeConfig]);
        self::assertSame($expected['config']['page_templates'], $result['test_theme']['config']['page_templates']);
    }

    public function testProcessPageTemplateWithEmptyRoute(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The path "themes.test_theme.config.page_templates.templates.0.route_name" cannot contain an empty value,'
            . ' but got "".'
        );

        $themeConfig = [
            'label'  => 'test label',
            'config' => [
                'page_templates' => [
                    'templates' => [
                        [
                            'route_name' => ''
                        ]
                    ]
                ]
            ]
        ];
        $this->processConfiguration(['test_theme' => $themeConfig]);
    }

    public function testProcessPageTemplateWithEmptyKey(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The path "themes.test_theme.config.page_templates.templates.0.key" cannot contain an empty value,'
            . ' but got "".'
        );

        $themeConfig = [
            'label'  => 'test label',
            'config' => [
                'page_templates' => [
                    'templates' => [
                        [
                            'key' => ''
                        ]
                    ]
                ]
            ]
        ];
        $this->processConfiguration(['test_theme' => $themeConfig]);
    }

    public function testProcessPageTemplateWithEmptyLabel(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage(
            'The path "themes.test_theme.config.page_templates.templates.0.label" cannot contain an empty value,'
            . ' but got "".'
        );

        $themeConfig = [
            'label'  => 'test label',
            'config' => [
                'page_templates' => [
                    'templates' => [
                        [
                            'label' => ''
                        ]
                    ]
                ]
            ]
        ];
        $this->processConfiguration(['test_theme' => $themeConfig]);
    }
}
