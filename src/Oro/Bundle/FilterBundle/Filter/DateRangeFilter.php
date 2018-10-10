<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;

/**
 * The filter by date fields.
 */
class DateRangeFilter extends AbstractDateFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return DateRangeFilterType::NAME;
    }
}
