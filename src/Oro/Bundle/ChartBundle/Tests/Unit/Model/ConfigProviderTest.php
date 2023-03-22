<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Model;

use Oro\Bundle\ChartBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ChartBundle\Model\ConfigProvider;
use Oro\Bundle\ChartBundle\Tests\Unit\Fixtures\FirstTestBundle\FirstTestBundle;
use Oro\Bundle\ChartBundle\Tests\Unit\Fixtures\SecondTestBundle\SecondTestBundle;
use Oro\Bundle\ChartBundle\Tests\Unit\Fixtures\ThirdTestBundle\ThirdTestBundle;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;

class ConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private ConfigProvider $configurationProvider;

    protected function setUp(): void
    {
        $cacheFile = $this->getTempFile('ChartConfigurationProvider');

        $this->configurationProvider = new ConfigProvider($cacheFile, false);

        $bundle1 = new FirstTestBundle();
        $bundle2 = new SecondTestBundle();
        $bundle3 = new ThirdTestBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2),
                $bundle3->getName() => get_class($bundle3)
            ]);
    }

    public function testConfiguration()
    {
        $expectedConfiguration = [
            'line_chart'     => [
                'label'            => 'Line Chart',
                'data_schema'      => [
                    [
                        'label'        => 'Category (X axis)',
                        'name'         => 'label',
                        'required'     => true,
                        'type_filter'  => [],
                        'default_type' => 'string'
                    ],
                    [
                        'label'        => 'Value (Y axis)',
                        'name'         => 'value',
                        'required'     => true,
                        'type_filter'  => [],
                        'default_type' => 'string'
                    ]
                ],
                'settings_schema'  => [
                    0 => [
                        'name'    => 'connect_dots_with_line',
                        'label'   => 'Connect line with dots',
                        'type'    => 'boolean',
                        'options' => [
                            'required' => true
                        ]
                    ]
                ],
                'default_settings' => [
                    'chartColors'         => [
                        '#ACD39C',
                        '#BE9DE2',
                        '#6598DA',
                        '#ECC87E',
                        '#A4A2F6',
                        '#6487BF',
                        '#65BC87'
                    ],
                    'chartFontSize'       => 9,
                    'chartFontColor'      => '#454545',
                    'chartHighlightColor' => '#FF5E5E',
                ],
                'data_transformer' => 'some_service_id',
                'template'         => '@FirstTest/Chart/lineChart.html.twig',
                'xaxis'            => [
                    'mode'    => 'normal',
                    'noTicks' => 5
                ]
            ],
            'advanced_chart' => [
                'label'            => 'Advanced Chart (overridden)',
                'template'         => '@SecondTest/Chart/advancedChart.html.twig',
                'data_schema'      => [],
                'settings_schema'  => [],
                'default_settings' => ['foo' => 'bar', 'chartColors' => 'testColor'],
                'xaxis'            => [
                    'mode'    => 'normal',
                    'noTicks' => 5
                ]
            ]
        ];

        self::assertEquals(
            array_keys($expectedConfiguration),
            $this->configurationProvider->getChartNames()
        );
        foreach (array_keys($expectedConfiguration) as $chartName) {
            $this->assertTrue(
                $this->configurationProvider->hasChartConfig($chartName),
                $chartName
            );
            $this->assertEquals(
                $expectedConfiguration[$chartName],
                $this->configurationProvider->getChartConfig($chartName),
                $chartName
            );
        }
    }

    public function testHasChartConfigForUnknownChart()
    {
        $this->assertFalse($this->configurationProvider->hasChartConfig('unknown'));
    }

    public function testGetChartConfigForUnknownChart()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("Can't find configuration for chart: unknown");

        $this->configurationProvider->getChartConfig('unknown');
    }
}
