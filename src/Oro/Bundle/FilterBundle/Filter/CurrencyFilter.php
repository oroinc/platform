<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;

/**
 * The filter by a monetary value or a range of monetary values.
 */
class CurrencyFilter extends NumberRangeFilter
{
    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'number-range';
        $params[FilterUtility::FORM_OPTIONS_KEY]['data_type'] = NumberFilterType::DATA_DECIMAL;

        parent::init($name, $params);
    }
}
