<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DateRangeFilterType;

abstract class AbstractDateFilter extends AbstractFilter
{
    /** DateTime object as string format */
    const DATETIME_FORMAT = 'Y-m-d';

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
            $ds->setParameter($startDateParameterName, $dateStartValue);
        }
        if (null !== $dateEndValue) {
            $ds->setParameter($endDateParameterName, $dateEndValue);
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
        return $this->dateFilterUtility->parseData($this->get(FilterUtility::DATA_NAME_KEY), $data, $this->name);
    }

    /**
     * Build expression using "between" filtering
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $dateStartValue
     * @param string                           $dateEndValue
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
            $exprs[] = $ds->expr()->lte($fieldName, $endDateParameterName, true);
        }

        return call_user_func_array([$ds->expr(), $conditionType], $exprs);
    }

    /**
     * Apply expression using one condition (less or more)
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param                                  $dateValue
     * @param                                  $dateParameterName
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
     * @param string                           $dateStartValue
     * @param string                           $dateEndValue
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
                        $ds->expr()->gt($fieldName, $endDateParameterName, true)
                    );
                } else {
                    $expr = $ds->expr()->lt($fieldName, $startDateParameterName, true);
                }
            } else {
                $expr = $ds->expr()->gt($fieldName, $endDateParameterName, true);
            }

            return $expr;
        }
    }

    /**
     * Build expression using one condition (equal or not equal)
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $dateValue
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
     * @param string                           $dateStartValue
     * @param string                           $dateEndValue
     * @param string                           $startDateParameterName
     * @param string                           $endDateParameterName
     * @param                                  $fieldName
     *
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
}
