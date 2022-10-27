<?php

namespace Oro\Bundle\DashboardBundle\Filter;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetDateRangeType;
use Oro\Bundle\FilterBundle\Filter\AbstractDateFilter;

/**
 * The filter by a date value or a range of date values for a configuration of dashboard widgets.
 */
class DateRangeFilter extends AbstractDateFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return WidgetDateRangeType::class;
    }
}
