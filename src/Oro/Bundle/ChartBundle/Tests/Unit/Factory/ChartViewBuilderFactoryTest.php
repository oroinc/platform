<?php

namespace Oro\Bundle\ChartBundle\Tests\Unit\Factory;

use Oro\Bundle\ChartBundle\Factory\ChartViewBuilderFactory;
use Oro\Bundle\ChartBundle\Model\ChartViewBuilder;
use Oro\Bundle\ChartBundle\Model\ConfigProvider;
use PHPUnit\Framework\MockObject\MockObject;

class ChartViewBuilderFactoryTest extends \PHPUnit\Framework\TestCase
{
    private ChartViewBuilderFactory $chartViewBuilderFactory;

    private ChartViewBuilder|MockObject $chartViewBuilder;

    private ConfigProvider|MockObject $chartConfigProvider;

    protected function setUp(): void
    {
        $this->chartViewBuilder = $this->createMock(ChartViewBuilder::class);

        $this->chartConfigProvider = $this->createMock(ConfigProvider::class);

        $this->chartViewBuilderFactory = new ChartViewBuilderFactory(
            $this->chartViewBuilder,
            $this->chartConfigProvider
        );
    }

    public function testCreateChartViewBuilder(): void
    {
        $chartName = 'test_chart_name';
        $chartType = 'test_chart_type';
        $scaleType = 'test_scale_type';

        $chartConfig = [
            'data_schema' => [
                'label' => [
                    'type' => 'test_int_type',
                    'label' => 'oro.dashboard.chart.%s.label',
                ],
                'startLabel' => [
                    'type' => 'test_date_type',
                    'label' => 'oro.dashboard.chart.%s.start.label',
                ],
                'endLabel' => [
                    'type' => 'test_date_type',
                    'label' => 'oro.dashboard.chart.%s.end.label',
                ],
            ],
        ];

        $this
            ->chartConfigProvider
            ->expects(self::once())
            ->method('getChartConfig')
            ->with($chartName)
            ->willReturn($chartConfig);

        $this
            ->chartViewBuilder
            ->expects(self::once())
            ->method('setOptions')
            ->with([
                'name' => $chartType,
                'data_schema' => [
                    'label' => [
                        'type' => $scaleType,
                        'label' => 'oro.dashboard.chart.test_scale_type.label',
                    ],
                    'startLabel' => [
                        'type' => 'test_date_type',
                        'label' => 'oro.dashboard.chart.test_scale_type.start.label',
                    ],
                    'endLabel' => [
                        'type' => 'test_date_type',
                        'label' => 'oro.dashboard.chart.test_scale_type.end.label',
                    ],
                ],
            ])
            ->willReturn($this->chartViewBuilder);

        $chartViewBuilder = $this->chartViewBuilderFactory->createChartViewBuilder($chartName, $chartType, $scaleType);

        self::assertSame($this->chartViewBuilder, $chartViewBuilder);
    }
}
