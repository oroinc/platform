<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * The base class for different kind of "datetime", "date" and "time" filters.
 * IMPORTANT: take into account that "between" and "not between" expressions are different
 * from such expressions in filters for numberic fields. The difference is that
 * for date and datetime fields these expressions are not include the end value.
 * This is done to prevent loss of data related to ending minutes, seconds, milliseconds, etc.
 * For example to corrent filtering of all records created on May 1, 2018, the following expression
 * should be used: "createdAt >= 2018-05-01 00:00:00 AND createdAt < 2018-05-02 00:00:00".
 * The expression like "createdAt >= 2018-05-01 00:00:00 AND createdAt <= 2018-05-01 23:59:59"
 * is incorrect and leads to loss of data created at the last second of the day.
 */
abstract class AbstractDateFilter extends AbstractFilter
{
    /** @var DateFilterUtility */
    protected $dateFilterUtility;

    /**
     * {@inheritdoc}
     */
    protected $joinOperators = [
        DateRangeFilterType::TYPE_NOT_BETWEEN => DateRangeFilterType::TYPE_BETWEEN,
        DateRangeFilterType::TYPE_NOT_EQUAL   => DateRangeFilterType::TYPE_EQUAL,
    ];

    public function __construct(
        FormFactoryInterface $factory,
        FilterUtility $util,
        DateFilterUtility $dateFilterUtility
    ) {
        parent::__construct($factory, $util);
        $this->dateFilterUtility = $dateFilterUtility;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        $dateStartValue = $data['date_start'];
        $dateEndValue   = $data['date_end'];
        //Swap start and end dates if end date is behind start date
        if (null !== $dateStartValue && null !== $dateEndValue && $dateStartValue > $dateEndValue) {
            $end = $dateEndValue;
            $dateEndValue = $dateStartValue;
            $dateStartValue = $end;
        }

        $startDateParameterName = $ds->generateParameterName($this->getName());
        $endDateParameterName   = $ds->generateParameterName($this->getName());

        if (null !== $dateStartValue) {
            $this->setParameter($ds, $startDateParameterName, $dateStartValue);
        }
        if (null !== $dateEndValue) {
            $this->setParameter($ds, $endDateParameterName, $dateEndValue);
        }
        if ($data['type'] === DateRangeFilterType::TYPE_NOT_EQUAL &&
            $comparisonType === DateRangeFilterType::TYPE_EQUAL
        ) {
            list($startDateParameterName, $endDateParameterName) = [$endDateParameterName, $startDateParameterName];
            list($dateStartValue, $dateEndValue) = [$dateEndValue, $dateStartValue];
        }

        return $this->buildDependingOnType(
            $comparisonType,
            $ds,
            $dateStartValue,
            $dateEndValue,
            $startDateParameterName,
            $endDateParameterName,
            $data['field']
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function parseData($data)
    {
        $this->validateFieldName();

        return $this->dateFilterUtility->parseData($this->get(FilterUtility::DATA_NAME_KEY), $data, $this->name);
    }

    /**
     * Sets a parameter for the given data source.
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param string|integer                   $key   The parameter position or name.
     * @param mixed                            $value The parameter value.
     * @param string|null                      $type  The parameter type.
     */
    protected function setParameter(FilterDatasourceAdapterInterface $ds, $key, $value, $type = null)
    {
        $ds->setParameter($key, $value, $type);
    }

    /**
     * Build expression using "between" filtering
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param \DateTime                        $dateStartValue
     * @param \DateTime                        $dateEndValue
     * @param string                           $startDateParameterName
     * @param string                           $endDateParameterName
     * @param string                           $fieldName
     *
     * @return mixed
     */
    protected function buildFilterBetween(
        $ds,
        $dateStartValue,
        $dateEndValue,
        $startDateParameterName,
        $endDateParameterName,
        $fieldName
    ) {
        // check if date part applied and start date greater than end
        $conditionType = ($dateStartValue > $dateEndValue && strpos($fieldName, '(') !== false) ? 'orX' : 'andX';
        $exprs = [];

        if (null !== $dateStartValue) {
            $exprs[] = $ds->expr()->gte($fieldName, $startDateParameterName, true);
        }

        if (null !== $dateEndValue) {
            $exprs[] = $ds->expr()->lt($fieldName, $endDateParameterName, true);
        }

        return call_user_func_array([$ds->expr(), $conditionType], $exprs);
    }

    /**
     * Apply expression using one condition (less or more)
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param \DateTime                        $dateValue
     * @param string                           $dateParameterName
     * @param string                           $fieldName
     * @param bool                             $isLess less/more mode, true if 'less than', false if 'more than'
     *
     * @return mixed
     */
    protected function buildFilterLessMore(
        $ds,
        $dateValue,
        $dateParameterName,
        $fieldName,
        $isLess
    ) {
        if (null !== $dateValue) {
            return $isLess
                ? $ds->expr()->lt($fieldName, $dateParameterName, true)
                : $ds->expr()->gt($fieldName, $dateParameterName, true);
        }
    }

    /**
     * Build  expression using "not between" filtering
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param \DateTime                        $dateStartValue
     * @param \DateTime                        $dateEndValue
     * @param string                           $startDateParameterName
     * @param string                           $endDateParameterName
     * @param string                           $fieldName
     *
     * @return mixed
     */
    protected function buildFilterNotBetween(
        $ds,
        $dateStartValue,
        $dateEndValue,
        $startDateParameterName,
        $endDateParameterName,
        $fieldName
    ) {
        if (null !== $dateStartValue || null !== $dateEndValue) {
            $expr = null;
            if (null !== $dateStartValue) {
                if (null !== $dateEndValue) {
                    $expr = $ds->expr()->orX(
                        $ds->expr()->lt($fieldName, $startDateParameterName, true),
                        $ds->expr()->gte($fieldName, $endDateParameterName, true)
                    );
                } else {
                    $expr = $ds->expr()->lt($fieldName, $startDateParameterName, true);
                }
            } else {
                $expr = $ds->expr()->gte($fieldName, $endDateParameterName, true);
            }

            return $expr;
        }
    }

    /**
     * Build expression using one condition (equal or not equal)
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param \DateTime                        $dateValue
     * @param string                           $dateParameterName
     * @param string                           $fieldName
     * @param bool                             $isEqual
     *
     * @return mixed
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

        return $isEqual
            ? $ds->expr()->eq($fieldName, $dateParameterName, true)
            : $ds->expr()->neq($fieldName, $dateParameterName, true);
    }

    /**
     * Builds filter depending on it's type
     *
     * @param int                              $type
     * @param FilterDatasourceAdapterInterface $ds
     * @param \DateTime                        $dateStartValue
     * @param \DateTime                        $dateEndValue
     * @param string                           $startDateParameterName
     * @param string                           $endDateParameterName
     * @param                                  $fieldName
     *
     * @return mixed
     */
    protected function buildDependingOnType(
        $type,
        $ds,
        $dateStartValue,
        $dateEndValue,
        $startDateParameterName,
        $endDateParameterName,
        $fieldName
    ) {
        switch ($type) {
            case DateRangeFilterType::TYPE_MORE_THAN:
                return $this->buildFilterLessMore(
                    $ds,
                    $dateStartValue,
                    $startDateParameterName,
                    $fieldName,
                    false
                );
            case DateRangeFilterType::TYPE_LESS_THAN:
                return $this->buildFilterLessMore(
                    $ds,
                    $dateEndValue,
                    $endDateParameterName,
                    $fieldName,
                    true
                );
            case DateRangeFilterType::TYPE_NOT_BETWEEN:
                return $this->buildFilterNotBetween(
                    $ds,
                    $dateStartValue,
                    $dateEndValue,
                    $startDateParameterName,
                    $endDateParameterName,
                    $fieldName
                );
            case DateRangeFilterType::TYPE_EQUAL:
                return $this->buildFilterEqual(
                    $ds,
                    $dateStartValue,
                    $startDateParameterName,
                    $fieldName,
                    true
                );
            case DateRangeFilterType::TYPE_NOT_EQUAL:
                return $this->buildFilterEqual(
                    $ds,
                    $dateEndValue,
                    $endDateParameterName,
                    $fieldName,
                    false
                );
            default:
            case DateRangeFilterType::TYPE_BETWEEN:
                return $this->buildFilterBetween(
                    $ds,
                    $dateStartValue,
                    $dateEndValue,
                    $startDateParameterName,
                    $endDateParameterName,
                    $fieldName
                );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $formView = $this->getForm()->createView();

        $metadata                          = parent::getMetadata();
        $metadata['typeValues']            = $formView->vars['type_values'];
        $metadata['dateParts']             = $formView->vars['date_parts'];
        $metadata['externalWidgetOptions'] = array_merge(
            $formView->vars['widget_options'],
            ['dateVars' => $formView->vars['date_vars']]
        );

        return $metadata;
    }

    /**
     * Validates the filter field name.
     */
    protected function validateFieldName()
    {
        QueryBuilderUtil::checkField($this->get(FilterUtility::DATA_NAME_KEY));
    }
}
