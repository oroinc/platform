<?php

namespace Oro\Bundle\ChartBundle\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Model\Data\ArrayData;
use Oro\Bundle\ChartBundle\Model\Data\DataInterface;

class PieChartDataTransformer implements TransformerInterface
{
    const FRACTION_INPUT_DATA_FIELD = 'fraction_input_data_field';
    const FRACTION_OUTPUT_DATA_FIELD = 'fraction_output_data_field';

    /**
     * Transform data to pie chart specific format
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
     *         "fraction_input_data_field" => "value"
     *         "fraction_output_data_field" => "fraction"
     *     ),
     *   )
     *
     * @return DataInterface
     */
    public function transform(DataInterface $data, array $chartOptions)
    {
        $inputKey = $chartOptions['settings'][self::FRACTION_INPUT_DATA_FIELD];
        $outputKey = $chartOptions['settings'][self::FRACTION_OUTPUT_DATA_FIELD];

        $inputArrayData = $data->toArray();
        $total = 0;

        foreach ($inputArrayData as $row) {
            $total += $row[$inputKey];
        }

        $resultArray = array();

        foreach ($inputArrayData as $row) {
            $row[$outputKey] =  $total > 0 ? round($row[$inputKey] / $total, 4) : 1;
            $resultArray[] = $row;
        }

        return new ArrayData($resultArray);
    }
}
