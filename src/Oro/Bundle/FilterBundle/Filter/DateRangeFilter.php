<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;

/**
 * The filter for "date" fields.
 */
class DateRangeFilter extends AbstractDateFilter
{
    /**
     * {@inheritdoc}
     */
    protected function setParameter(FilterDatasourceAdapterInterface $ds, $key, $value, $type = null)
    {
        if (null === $type && $value instanceof \DateTime) {
            $type = Type::DATE;
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
