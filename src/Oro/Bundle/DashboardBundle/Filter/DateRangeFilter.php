<?php

namespace Oro\Bundle\DashboardBundle\Filter;

use Oro\Bundle\DashboardBundle\Form\Type\WidgetDateRangeType;
use Oro\Bundle\FilterBundle\Filter\AbstractDateFilter;

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
