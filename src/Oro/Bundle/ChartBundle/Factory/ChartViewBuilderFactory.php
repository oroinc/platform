<?php

namespace Oro\Bundle\ChartBundle\Factory;

use Oro\Bundle\ChartBundle\Model\ChartViewBuilder;
use Oro\Bundle\ChartBundle\Model\ConfigProvider as ChartConfigProvider;

/**
 * Factory class for creation ChartViewBuilder instances with simplified configuration options
 */
class ChartViewBuilderFactory
{
    private ChartViewBuilder $chartViewBuilder;

    private ChartConfigProvider $chartConfigProvider;

    public function __construct(ChartViewBuilder $chartViewBuilder, ChartConfigProvider $chartConfigProvider)
    {
        $this->chartViewBuilder = $chartViewBuilder;
        $this->chartConfigProvider = $chartConfigProvider;
    }

    public function createChartViewBuilder(
        string $chartName,
        string $chartType,
        string $scaleType
    ): ChartViewBuilder {
        $chartOptions = array_merge_recursive(
            ['name' => $chartType],
            $this->chartConfigProvider->getChartConfig($chartName)
        );

        $chartOptions['data_schema']['label']['type'] = $scaleType;

        $chartOptions['data_schema']['label']['label'] = sprintf(
            $chartOptions['data_schema']['label']['label'],
            $scaleType
        );
        if (isset($chartOptions['data_schema']['startLabel']['label'])) {
            $chartOptions['data_schema']['startLabel']['label'] = sprintf(
                $chartOptions['data_schema']['startLabel']['label'],
                $scaleType
            );
        }
        if (isset($chartOptions['data_schema']['endLabel']['label'])) {
            $chartOptions['data_schema']['endLabel']['label'] = sprintf(
                $chartOptions['data_schema']['endLabel']['label'],
                $scaleType
            );
        }

        return $this->chartViewBuilder->setOptions($chartOptions);
    }
}
