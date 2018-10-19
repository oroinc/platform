<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;

/**
 * The filter for "datetime" fields.
 */
class DateTimeRangeFilter extends AbstractDateFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return DateTimeRangeFilterType::class;
    }
}
