<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DateTimeRangeFilterType;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Class that provide functionality that helps to filter by date time type fields
 */
class DateTimeRangeFilter extends AbstractDateFilter
{
    /**
     * DateTime object as string format
     */
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return DateTimeRangeFilterType::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildFilterEqual(
        $ds,
        $dateValue,
        $dateParameterName,
        $fieldName,
        $isEqual
    ) {
        if (null === $dateValue) {
            return null;
        }

        $expressions = [];

        /**
         * We have to filter with 1 minute interval
         * because filtering by seconds is inconvenient for equal filter type
         */
        $dateEndValue = clone $dateValue;
        $dateEndValue->modify('+1 minute');
        $endDateParameterName = $ds->generateParameterName($this->getName());
        $this->setParameter($ds, $endDateParameterName, $dateEndValue);

        /** Check data to avoid sql injections */
        QueryBuilderUtil::checkField($fieldName);
        QueryBuilderUtil::checkParameter($dateParameterName);
        QueryBuilderUtil::checkParameter($endDateParameterName);

        if ($isEqual) {
            $expressions[] = $ds->expr()->gte($fieldName, $dateParameterName, true);
            $expressions[] = $ds->expr()->lte($fieldName, $endDateParameterName, true);
            $conditionType = 'andX';
        } else {
            $expressions[] = $ds->expr()->lte($fieldName, $dateParameterName, true);
            $expressions[] = $ds->expr()->gte($fieldName, $endDateParameterName, true);
            $conditionType = 'orX';
        }

        return call_user_func_array([$ds->expr(), $conditionType], $expressions);
    }
}
