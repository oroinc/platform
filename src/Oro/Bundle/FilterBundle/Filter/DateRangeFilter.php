<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;

/**
 * The filter by a date value or a range of date values.
 */
class DateRangeFilter extends AbstractDateFilter
{
    /**
     * {@inheritdoc}
     */
    protected function setParameter(FilterDatasourceAdapterInterface $ds, $key, $value, $type = null)
    {
        if (null === $type && $value instanceof \DateTime) {
            $type = Types::DATE_MUTABLE;
            $value = new \DateTime($value->format('Y-m-d'), new \DateTimeZone('UTC'));
        }
        parent::setParameter($ds, $key, $value, $type);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return DateRangeFilterType::class;
    }
}
