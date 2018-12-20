<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Exception\LogicException;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;

/**
 * Basic number filter which supports multiple operators, for "orm" datasource.
 */
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
        return NumberFilterType::class;
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
        if ($this->isArrayComparison($data['type'])) {
            return $this->getArrayValues($data);
        }

        if (!is_numeric($data['value'])) {
            if (in_array($data['type'], [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])) {
                return $data;
            }

            return false;
        }

        $data['value'] = $this->applyDivisor($data['value']);

        return $data;
    }

    /**
     * Build an expression used to filter data
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param int $comparisonType
     * @param string $fieldName
     * @param string $parameterName
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
            case NumberFilterType::TYPE_IN:
                return $ds->expr()->in($fieldName, $parameterName, true);
            case NumberFilterType::TYPE_NOT_IN:
                return $ds->expr()->notIn($fieldName, $parameterName, true);
            default:
                return $ds->expr()->eq($fieldName, $parameterName, true);
        }
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string $fieldName
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

        $formView = $this->getForm()->createView();
        $metadata['formatterOptions'] = $formView->vars['formatter_options'];
        $metadata['arraySeparator'] = $formView->vars['array_separator'];
        $metadata['arrayOperators'] = $formView->vars['array_operators'];
        $metadata['dataType'] = $formView->vars['data_type'];

        return $metadata;
    }

    /**
     * @param string $comparisonType
     * @return bool
     */
    protected function isArrayComparison($comparisonType): bool
    {
        return in_array($comparisonType, NumberFilterType::ARRAY_TYPES);
    }

    /**
     * @param array $data
     * @return array
     */
    protected function getArrayValues(array $data)
    {
        $data['value'] = array_filter(
            array_map(
                function ($value) {
                    $value = trim($value);
                    if (!is_numeric($value)) {
                        return null;
                    }

                    return $value;
                },
                explode(NumberFilterType::ARRAY_SEPARATOR, $data['value'])
            ),
            function ($value) {
                return $value !== null;
            }
        );

        return $data;
    }

    /**
     * Apply configured divisor to a numeric raw value. For filtering this means to multiply the filter value.
     *
     * @param mixed $value
     *
     * @return float
     */
    protected function applyDivisor($value)
    {
        if (!is_numeric($value)) {
            return $value;
        }

        if ($divisor = $this->getOr(FilterUtility::DIVISOR_KEY)) {
            $value = $value * $divisor;
        }

        return $value;
    }
}
