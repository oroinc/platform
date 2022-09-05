<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Extension;

use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfigurationProvider;
use Oro\Bundle\LayoutBundle\Tests\Unit\Stubs\Bundles\TestAppRoot\SrcStubFolder\AppKernelStub;
use Oro\Bundle\LayoutBundle\Tests\Unit\Stubs\Bundles\TestBundle\TestBundle;
use Oro\Bundle\LayoutBundle\Tests\Unit\Stubs\Bundles\TestBundle2\TestBundle2;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\Assert\ArrayContainsConstraint;
use Oro\Component\Testing\TempDirExtension;

class ThemeConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var ThemeConfigurationProvider */
    private $configurationProvider;

    protected function setUp(): void
    {
        $bundle1 = new TestBundle();
        $bundle2 = new TestBundle2();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2),
                'app.kernel' => AppKernelStub::class
            ]);

        $this->configurationProvider = new ThemeConfigurationProvider(
            $this->getTempFile('ConfigurationProvider'),
            false,
            new ThemeConfiguration(),
            '[a-zA-Z][a-zA-Z0-9_\-:]*'
        );
    }

    public function testGetThemeNames()
    {
        $result = $this->configurationProvider->getThemeNames();
        sort($result);
        $this->assertEquals(['base', 'oro-app-root-based', 'oro-black'], $result);
    }

    public function testGetThemeDefinitionForUnknownTheme()
    {
        $this->assertNull(
            $this->configurationProvider->getThemeDefinition('unknown')
        );
    }

    public function testGetThemeDefinitionForEmptyTheme()
    {
        $this->assertEquals(
            [
                'label'              => 'Base theme',
                'groups'             => [],
                'image_placeholders' => [],
                'extra_js_builds'  => []
            ],
            $this->configurationProvider->getThemeDefinition('base')
        );
    }

    public function testGetThemeDefinition()
    {
        $expected = [
            'label'              => 'Oro Black theme',
            'description'        => 'Oro Black theme description',
            'groups'             => ['another'],
            'icon'               => 'oro-black.ico',
            'image_placeholders' => [],
            'config'             => [
                'assets'         => [
                    'css' => [
                        'inputs'  => ['bundles/test/css/scss/main.scss'],
                        'filters' => ['some_filter'],
                        'auto_rtl_inputs' => ['bundles/test/**']
                    ]
                ],
                'images'         => [
                    'types'      => [
                        'main' => [
                            'label'      => 'main image type',
                            'dimensions' => ['original', 'popup'],
                            'max_number' => 1
                        ]
                    ],
                    'dimensions' => [
                        'original' => [
                            'width'   => null,
                            'height'  => null,
                            'options' => [
                                'applyProductImageWatermark' => true
                            ]
                        ],
                        'popup'    => [
                            'width'   => 610,
                            'height'  => 610,
                            'options' => [
                                'applyProductImageWatermark' => false
                            ]
                        ]
                    ]
                ],
                'page_templates' => [
                    'templates' => [
                        [
                            'label'       => 'Some label',
                            'description' => null,
                            'key'         => 'some_key',
                            'route_name'  => 'some_route_name',
                            'enabled'     => null,
                            'screenshot'  => null
                        ],
                        [
                            'label'       => 'Some label (disabled)',
                            'description' => 'Disabled',
                            'key'         => 'some_key_disabled',
                            'route_name'  => 'some_route_name_disabled',
                            'enabled'     => false,
                            'screenshot'  => null
                        ],
                        [
                            'label'       => 'Two columns page',
                            'description' => 'Two columns template',
                            'key'         => 'two-columns',
                            'route_name'  => 'oro_two_columns_view',
                            'enabled'     => null,
                            'screenshot'  => null
                        ]
                    ],
                    'titles'    => [
                        'route_1'              => 'Title for route 1',
                        'route_2'              => 'Title for route 2',
                        'oro_two_columns_view' => 'Two Columns Page'
                    ]
                ],
                'extra_config'   => [
                    'label' => 'Sample label'
                ]
            ],
            'extra_js_builds'  => []
        ];

        $actual = $this->configurationProvider->getThemeDefinition('oro-black');

        // use both ArrayContainsConstraint and assertEquals to strict check of each value in array
        $this->assertThat($actual, new ArrayContainsConstraint($expected));
        self::assertEquals($expected, $actual);
    }
}
