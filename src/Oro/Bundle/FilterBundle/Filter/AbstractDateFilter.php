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
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->dateFilterUtility->parseData($this->get(FilterUtility::DATA_NAME_KEY), $data, $this->name);
        if (!$data) {
            return false;
        }

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

        $this->applyDependingOnType(
            $data['type'],
            $ds,
            $dateStartValue,
            $dateEndValue,
            $startDateParameterName,
            $endDateParameterName,
            $data['field']
        );

        if (null !== $dateStartValue) {
            $ds->setParameter($startDateParameterName, $dateStartValue);
        }
        if (null !== $dateEndValue) {
            $ds->setParameter($endDateParameterName, $dateEndValue);
        }

        return true;
    }

    /**
     * Apply expression using "between" filtering
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $dateStartValue
     * @param string                           $dateEndValue
     * @param string                           $startDateParameterName
     * @param string                           $endDateParameterName
     * @param string                           $fieldName
     */
    protected function applyFilterBetween(
        $ds,
        $dateStartValue,
        $dateEndValue,
        $startDateParameterName,
        $endDateParameterName,
        $fieldName
    ) {
        // check if date part applied and start date greater than end
        if ($dateStartValue > $dateEndValue && strpos($fieldName, '(') !== false) {
            $conditionType = FilterUtility::CONDITION_OR;
        } else {
            $conditionType = FilterUtility::CONDITION_AND;
        }

        if (null !== $dateStartValue) {
            $this->applyFilterToClause(
                $ds,
                $ds->expr()->gte($fieldName, $startDateParameterName, true),
                $conditionType
            );
        }

        if (null !== $dateEndValue) {
            $this->applyFilterToClause(
                $ds,
                $ds->expr()->lte($fieldName, $endDateParameterName, true),
                $conditionType
            );
        }
    }

    /**
     * Apply expression using one condition (less or more)
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param                                  $dateValue
     * @param                                  $dateParameterName
     * @param string                           $fieldName
     * @param bool                             $isLess less/more mode, true if 'less than', false if 'more than'
     */
    protected function applyFilterLessMore(
        $ds,
        $dateValue,
        $dateParameterName,
        $fieldName,
        $isLess
    ) {
        if (null !== $dateValue) {
            $expr = $isLess
                ? $ds->expr()->lt($fieldName, $dateParameterName, true)
                : $ds->expr()->gt($fieldName, $dateParameterName, true);
            $this->applyFilterToClause($ds, $expr);
        }
    }

    /**
     * Apply expression using "not between" filtering
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $dateStartValue
     * @param string                           $dateEndValue
     * @param string                           $startDateParameterName
     * @param string                           $endDateParameterName
     * @param string                           $fieldName
     */
    protected function applyFilterNotBetween(
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
            $this->applyFilterToClause($ds, $expr);
        }
    }

    /**
     * Apply expression using one condition (equal or not equal)
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $dateValue
     * @param string                           $dateParameterName
     * @param string                           $fieldName
     * @param bool                             $isEqual
     */
    protected function applyFilterEqual(
        $ds,
        $dateValue,
        $dateParameterName,
        $fieldName,
        $isEqual
    ) {
        if (null !== $dateValue) {
            $expr = $isEqual
                ? $ds->expr()->eq($fieldName, $dateParameterName, true)
                : $ds->expr()->neq($fieldName, $dateParameterName, true);
            $this->applyFilterToClause($ds, $expr);
        }
    }

    /**
     * Applies filter depending on it's type
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
    protected function applyDependingOnType(
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
                $this->applyFilterLessMore(
                    $ds,
                    $dateStartValue,
                    $startDateParameterName,
                    $fieldName,
                    false
                );
                break;
            case DateRangeFilterType::TYPE_LESS_THAN:
                $this->applyFilterLessMore(
                    $ds,
                    $dateEndValue,
                    $endDateParameterName,
                    $fieldName,
                    true
                );
                break;
            case DateRangeFilterType::TYPE_NOT_BETWEEN:
                $this->applyFilterNotBetween(
                    $ds,
                    $dateStartValue,
                    $dateEndValue,
                    $startDateParameterName,
                    $endDateParameterName,
                    $fieldName
                );
                break;
            case DateRangeFilterType::TYPE_EQUAL:
                $this->applyFilterEqual(
                    $ds,
                    $dateStartValue,
                    $startDateParameterName,
                    $fieldName,
                    true
                );
                break;
            case DateRangeFilterType::TYPE_NOT_EQUAL:
                $this->applyFilterEqual(
                    $ds,
                    $dateEndValue,
                    $endDateParameterName,
                    $fieldName,
                    false
                );
                break;
            default:
            case DateRangeFilterType::TYPE_BETWEEN:
                $this->applyFilterBetween(
                    $ds,
                    $dateStartValue,
                    $dateEndValue,
                    $startDateParameterName,
                    $endDateParameterName,
                    $fieldName
                );
                break;
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
