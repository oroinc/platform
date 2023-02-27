<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterTypeInterface;

/**
 * The filter by a numeric value or a range of numeric values.
 */
class NumberRangeFilter extends NumberFilter
{
    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return NumberRangeFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        if (!$this->isApplicable($data)) {
            return parent::buildExpr($ds, $comparisonType, $fieldName, $data);
        }

        return $this->buildRangeComparisonExpr(
            $ds,
            $comparisonType,
            $fieldName,
            $data['value'],
            $data['value_end']
        );
    }

    /**
     * @param mixed $data
     *
     * @return bool
     */
    protected function isApplicable($data)
    {
        return
            \is_array($data)
            && isset($data['type'])
            && $this->isApplicableType($data['type']);
    }

    /**
     * @param mixed $type
     *
     * @return bool
     */
    protected function isApplicableType($type): bool
    {
        return
            NumberRangeFilterTypeInterface::TYPE_BETWEEN === $type
            || NumberRangeFilterTypeInterface::TYPE_NOT_BETWEEN === $type;
    }

    /**
     * Build an expression used to filter data
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param int                              $comparisonType
     * @param string                           $fieldName
     * @param mixed                            $valueStart
     * @param mixed                            $valueEnd
     *
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
            if ($this->isValueRequired($comparisonType)) {
                $ds->setParameter($parameterName, $valueStart);
            }

            return $this->buildComparisonExpr($ds, $comparisonType, $fieldName, $parameterName);
        }

        $result = false;
        switch ($comparisonType) {
            case NumberRangeFilterTypeInterface::TYPE_BETWEEN:
                $result =  $this->buildBetweenExpr($ds, $fieldName, $valueStart, $valueEnd);
                break;
            case NumberRangeFilterTypeInterface::TYPE_NOT_BETWEEN:
                $result = $this->buildNotBetweenExpr($ds, $fieldName, $valueStart, $valueEnd);
                break;
        }

        return $result;
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $fieldName
     * @param mixed                            $valueStart
     * @param mixed                            $valueEnd
     *
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
        }

        if (null !== $valueStart) {
            $ds->setParameter($parameterStart, $valueStart);

            return $ds->expr()->gte($fieldName, $parameterStart, true);
        }

        $ds->setParameter($parameterEnd, $valueEnd);

        return $ds->expr()->lte($fieldName, $parameterEnd, true);
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $fieldName
     * @param mixed                            $valueStart
     * @param mixed                            $valueEnd
     *
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
        }

        if ($valueStart) {
            $ds->setParameter($parameterStart, $valueStart);

            return $ds->expr()->lt($fieldName, $parameterStart, true);
        }

        $ds->setParameter($parameterEnd, $valueEnd);

        return $ds->expr()->gt($fieldName, $parameterEnd, true);
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        $type = null;
        if (isset($data['type'])) {
            $type = $this->normalizeType($data['type']);
        }
        if ($this->isApplicableType($type)) {
            if (!isset($data['value']) || '' === $data['value']) {
                $data['value'] = null;
            } else {
                $this->assertScalarValue($data['value']);
                $data['value'] = $this->normalizeValue($data['value']);
            }
            if (!isset($data['value_end']) || '' === $data['value_end']) {
                $data['value_end'] = null;
            } else {
                $this->assertScalarValue($data['value_end']);
                $data['value_end'] = $this->normalizeValue($data['value_end']);
            }
        } else {
            $data = parent::prepareData($data);
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function parseValue(array $data)
    {
        if (!$this->isApplicable($data)) {
            return parent::parseValue($data);
        }

        $valueStart = null;
        if (isset($data['value'])) {
            $valueStart = $this->parseNumericValue($data['value']);
            if (false === $valueStart) {
                return false;
            }
        }
        $data['value'] = $valueStart;

        $valueEnd = null;
        if (isset($data['value_end'])) {
            $valueEnd = $this->parseNumericValue($data['value_end']);
            if (false === $valueEnd) {
                return false;
            }
        }
        $data['value_end'] = $valueEnd;

        if ((null === $data['value'] && null === $valueEnd) || false === $valueStart || false === $valueEnd) {
            return false;
        }

        return $data;
    }
}
