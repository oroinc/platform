<?php

namespace Oro\Bundle\ChartBundle\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Model\Data\DataInterface;

/**
 * Defines the contract for data transformers that convert chart data to chart-specific formats.
 *
 * Implementations of this interface are responsible for transforming raw data into formats
 * suitable for specific chart types. Each transformer receives data and chart options
 * (including data schema and settings) and returns transformed data ready for rendering.
 * This allows different chart types to have custom data processing logic while maintaining
 * a consistent interface.
 */
interface TransformerInterface
{
    /**
     * Transform data to chart specific format
     *
     * @param DataInterface $data
     * @param array $chartOptions
     *   Format of $chartOptions variable:
     *   array(
     *     "name" => "chart_name",
     *     "data_schema" => array(
     *         "label" => array("field_name" => "name", "label" => "oro.xxx.firstName"),
     *         "value" => array("field_name" => "salary", "label" => "oro.xxx.salary"),
     *     ),
     *     "settings" => array(
     *         "foo" => "bar"
     *     ),
     *   )
     *
     * @return DataInterface
     */
    public function transform(DataInterface $data, array $chartOptions);
}
