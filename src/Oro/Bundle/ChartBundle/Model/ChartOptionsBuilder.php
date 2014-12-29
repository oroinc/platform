<?php

namespace Oro\Bundle\ChartBundle\Model;

class ChartOptionsBuilder
{
    /**
     * @param array $chartOptions
     * @param array $gridConfig
     * @return array
     */
    public function buildOptions(array $chartOptions, array $gridConfig)
    {
        if (isset($chartOptions['data_schema']) && isset($gridConfig['source']['query_config']['column_aliases'])) {
            $columnAliases = $gridConfig['source']['query_config']['column_aliases'];
            foreach ($chartOptions['data_schema'] as $key => &$value) {
                $value = isset($columnAliases[$value]) ? $columnAliases[$value] : $value;
            }
        }

        return $chartOptions;
    }
}
