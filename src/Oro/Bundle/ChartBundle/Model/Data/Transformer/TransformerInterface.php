<?php

namespace Oro\Bundle\ChartBundle\Model\Data\Transformer;

use Oro\Bundle\ChartBundle\Model\Data\DataInterface;

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
