<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;

/**
 * The filter by a datetime value or a range of datetime values.
 */
class DateTimeRangeFilter extends AbstractDateFilter
{
    public const DATE_FORMAT = 'yyyy-MM-dd HH:mm';

    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return DateTimeRangeFilterType::class;
    }
}
