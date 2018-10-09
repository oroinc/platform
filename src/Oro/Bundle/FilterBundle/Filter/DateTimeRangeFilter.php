<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;

/**
 * The filter by datetime fields.
 */
class DateTimeRangeFilter extends AbstractDateFilter
{
    /**
     * DateTime object as string format
     * @deprecated will be removed in 3.0
     */
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return DateTimeRangeFilterType::NAME;
    }
}
