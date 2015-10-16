<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

class NumberRangeFilter extends NumberFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return NumberRangeFilterType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$this->isApplicable($data)) {
            return parent::apply($ds, $data);
        }

        if (false === ($data = $this->parseData($data))) {
            return false;
        }

        $type = $data['type'];

        $this->applyFilterToClause(
            $ds,
            $this->buildRangeComparisonExpr(
                $ds,
                $type,
                $this->get(FilterUtility::DATA_NAME_KEY),
                $data['value'],
                $data['value_end']
            )
        );

        return true;
    }

    /**
     * @param mixed $data
     * @return boolean
     */
    protected function isApplicable($data)
    {
        if (!is_array($data) || !isset($data['type'])) {
            return false;
        }

        $types = [
            NumberRangeFilterType::TYPE_BETWEEN,
            NumberRangeFilterType::TYPE_NOT_BETWEEN,
        ];

        return in_array($data['type'], $types, true);
    }

    /**
     * Build an expression used to filter data
     *
     * @param FilterDatasourceAdapterInterface  $ds
     * @param int                               $comparisonType
     * @param string                            $fieldName
     * @param string                            $valueStart
     * @param string                            $valueEnd
     * @return string
     */
    protected function buildRangeComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName,
        $valueStart = null,
        $valueEnd = null
    ) {
        if (!$this->isApplicable(['type' => $comparisonType])) {
            $parameterName = $ds->generateParameterName($this->getName());

            if (!in_array($comparisonType, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY], true)) {
                $ds->setParameter($parameterName, $valueStart);
            }

            return $this->buildComparisonExpr($ds, $comparisonType, $fieldName, $parameterName);
        }

        switch ($comparisonType) {
            case NumberRangeFilterType::TYPE_BETWEEN:
                $res =  $this->buildBetweenExpr($ds, $fieldName, $valueStart, $valueEnd);

                break;

            case NumberRangeFilterType::TYPE_NOT_BETWEEN:
                $res = $this->buildNotBetweenExpr($ds, $fieldName, $valueStart, $valueEnd);

                break;

            default:
                $res = false;
        }

        return $res;
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string $fieldName
     * @param mixed $valueStart
     * @param mixed $valueEnd
     * @return string
     */
    protected function buildBetweenExpr(FilterDatasourceAdapterInterface $ds, $fieldName, $valueStart, $valueEnd)
    {
        $parameterStart = $ds->generateParameterName($this->getName());
        $parameterEnd = $ds->generateParameterName($this->getName());

        if (null !== $valueStart && null !== $valueEnd) {
            $ds->setParameter($parameterStart, $valueStart);
            $ds->setParameter($parameterEnd, $valueEnd);

            return $ds->expr()->andX(
                $ds->expr()->gte($fieldName, $parameterStart, true),
                $ds->expr()->lte($fieldName, $parameterEnd, true)
            );
        } elseif (null !== $valueStart) {
            $ds->setParameter($parameterStart, $valueStart);

            return $ds->expr()->gte($fieldName, $parameterStart, true);
        } else {
            $ds->setParameter($parameterEnd, $valueEnd);

            return $ds->expr()->lte($fieldName, $parameterEnd, true);
        }
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string $fieldName
     * @param mixed $valueStart
     * @param mixed $valueEnd
     * @return string
     */
    protected function buildNotBetweenExpr(FilterDatasourceAdapterInterface $ds, $fieldName, $valueStart, $valueEnd)
    {
        $parameterStart = $ds->generateParameterName($this->getName());
        $parameterEnd = $ds->generateParameterName($this->getName());

        if ($valueStart && $valueEnd) {
            $ds->setParameter($parameterStart, $valueStart);
            $ds->setParameter($parameterEnd, $valueEnd);

            return $ds->expr()->orX(
                $ds->expr()->lt($fieldName, $parameterStart, true),
                $ds->expr()->gt($fieldName, $parameterEnd, true)
            );
        } elseif ($valueStart) {
            $ds->setParameter($parameterStart, $valueStart);

            return $ds->expr()->lt($fieldName, $parameterStart, true);
        } else {
            $ds->setParameter($parameterEnd, $valueEnd);

            return $ds->expr()->gt($fieldName, $parameterEnd, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function parseData($data)
    {
        if (!$this->isApplicable($data)) {
            return parent::parseData($data);
        }

        if (!is_array($data) || (!array_key_exists('value', $data) && !array_key_exists('value_end', $data))) {
            return false;
        }

        if (!isset($data['value']) && !isset($data['value_end'])) {
            return false;
        }

        if (!isset($data['type'])) {
            $data['type'] = null;
        }

        $this->parseValue($data);

        return $data;
    }

    /**
     * @param array $data
     */
    protected function parseValue(array &$data)
    {
        switch ($data['type']) {
            case FilterUtility::TYPE_EMPTY:
            case FilterUtility::TYPE_NOT_EMPTY:
                $data['value'] = null;
                $data['value_end'] = null;
                break;

            case NumberRangeFilterType::TYPE_BETWEEN:
            case NumberRangeFilterType::TYPE_NOT_BETWEEN:
                if (!isset($data['value'])) {
                    $data['value'] = null;
                }
                if (!isset($data['value_end'])) {
                    $data['value_end'] = null;
                }
                break;
        }
    }
}
