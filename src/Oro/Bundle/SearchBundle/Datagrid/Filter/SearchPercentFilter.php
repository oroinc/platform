<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;

class SearchPercentFilter extends SearchNumberRangeFilter
{
    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'number-range';
        $params[FilterUtility::FORM_OPTIONS_KEY]['data_type'] = NumberFilterType::PERCENT;

        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    protected function parseData($data)
    {
        $data = parent::parseData($data);

        if ($data) {
            $valueKeys = ['value', 'value_end'];
            foreach ($valueKeys as $key) {
                if (is_numeric($data[$key])) {
                    $data[$key] /= 100;
                }
            }
        }

        return $data;
    }
}
