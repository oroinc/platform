<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

class NumberFilter extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    protected $joinOperators = [
        FilterUtility::TYPE_NOT_EMPTY => FilterUtility::TYPE_EMPTY,
        NumberFilterType::TYPE_NOT_EQUAL => NumberFilterType::TYPE_EQUAL,
    ];

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return NumberFilterType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        $parameterName = $ds->generateParameterName($this->getName());
        if (!in_array($comparisonType, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])) {
            $ds->setParameter($parameterName, $data['value']);
        }

        return $this->buildComparisonExpr(
            $ds,
            $comparisonType,
            $fieldName,
            $parameterName
        );
    }

    /**
     * @param mixed $data
     *
     * @return array|bool
     */
    public function parseData($data)
    {
        if (!is_array($data) || !array_key_exists('value', $data)) {
            return false;
        }

        $data['type'] = isset($data['type']) ? $data['type'] : null;

        if (!is_numeric($data['value'])) {
            if (in_array($data['type'], [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])) {
                return $data;
            }

            return false;
        }

        return $data;
    }

    /**
     * Build an expression used to filter data
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param int                              $comparisonType
     * @param string                           $fieldName
     * @param string                           $parameterName
     * @return string
     */
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName,
        $parameterName
    ) {
        switch ($comparisonType) {
            case NumberFilterType::TYPE_GREATER_EQUAL:
                return $ds->expr()->gte($fieldName, $parameterName, true);
            case NumberFilterType::TYPE_GREATER_THAN:
                return $ds->expr()->gt($fieldName, $parameterName, true);
            case NumberFilterType::TYPE_LESS_EQUAL:
                return $ds->expr()->lte($fieldName, $parameterName, true);
            case NumberFilterType::TYPE_LESS_THAN:
                return $ds->expr()->lt($fieldName, $parameterName, true);
            case NumberFilterType::TYPE_NOT_EQUAL:
                return $ds->expr()->neq($fieldName, $parameterName, true);
            case FilterUtility::TYPE_EMPTY:
                if ($this->isAggregateField($ds, $fieldName)) {
                    $fieldName = $ds->expr()->coalesce([$fieldName]);
                }

                return $ds->expr()->isNull($fieldName);
            case FilterUtility::TYPE_NOT_EMPTY:
                if ($this->isAggregateField($ds, $fieldName)) {
                    $fieldName = $ds->expr()->coalesce([$fieldName]);
                }

                return $ds->expr()->isNotNull($fieldName);
            default:
                return $ds->expr()->eq($fieldName, $parameterName, true);
        }
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $fieldName
     *
     * @return bool
     */
    protected function isAggregateField(FilterDatasourceAdapterInterface $ds, $fieldName)
    {
        return (bool)preg_match('/(?<![\w:.])(\w+)\s*\(/im', $ds->getFieldByAlias($fieldName));
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();

        $formView                     = $this->getForm()->createView();
        $metadata['formatterOptions'] = $formView->vars['formatter_options'];

        return $metadata;
    }
}
