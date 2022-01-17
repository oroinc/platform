<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Doctrine\DBAL\Platforms\PostgreSQL92Platform;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;

/**
 * The filter by a string value.
 */
class StringFilter extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    protected $joinOperators = [
        FilterUtility::TYPE_NOT_EMPTY => FilterUtility::TYPE_EMPTY,
        TextFilterType::TYPE_NOT_CONTAINS => TextFilterType::TYPE_CONTAINS,
        TextFilterType::TYPE_NOT_IN => TextFilterType::TYPE_IN,
    ];

    /**
     * {@inheritDoc}
     */
    protected function buildExpr(FilterDatasourceAdapterInterface $ds, $comparisonType, $fieldName, $data)
    {
        $parameterName = $ds->generateParameterName($this->getName());
        $this->setCaseSensitivity($ds);
        $expr = $this->buildComparisonExpr(
            $ds,
            $comparisonType,
            $fieldName,
            $parameterName
        );
        $this->resetCaseSensitivity($ds);
        if ($this->isValueRequired($comparisonType)) {
            $ds->setParameter($parameterName, $this->convertValue($data['value']));
        }

        return $expr;
    }

    /**
     * @param array $data
     *
     * @return array|mixed|string
     */
    protected function convertValue($data)
    {
        if (is_array($data) && !$this->isCaseInsensitive()) {
            // used when e.g. we have type TextFilterType::TYPE_IN and search is case-sensitive
            return array_map([$this, 'convertData'], $data);
        }
        if (is_array($data)) {
            // used when e.g. we have type TextFilterType::TYPE_IN and search is case-insensitive
            return array_map('mb_strtolower', $data);
        }
        if (!$this->isCaseInsensitive()) {
            // used when e.g. we have type other then TextFilterType::TYPE_IN and search is case sensitive
            return $this->convertData($data);
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return TextFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseData($data)
    {
        $data = parent::parseData($data);

        if (!\is_array($data)) {
            return false;
        }

        if (!$this->isValueRequired($data['type'])) {
            return $data;
        }

        if (!isset($data['value']) || '' === $data['value']) {
            return false;
        }

        if (!$data['type']) {
            $data['type'] = TextFilterType::TYPE_EQUAL;
        }

        return $this->parseValue($data);
    }

    /**
     * @param array $data
     *
     * @return mixed
     */
    protected function parseValue(array $data)
    {
        switch ($data['type']) {
            case TextFilterType::TYPE_CONTAINS:
            case TextFilterType::TYPE_NOT_CONTAINS:
                $data['value'] = sprintf('%%%s%%', $data['value']);
                break;
            case TextFilterType::TYPE_STARTS_WITH:
                $data['value'] = sprintf('%s%%', $data['value']);
                break;
            case TextFilterType::TYPE_ENDS_WITH:
                $data['value'] = sprintf('%%%s', $data['value']);
                break;
            case TextFilterType::TYPE_IN:
            case TextFilterType::TYPE_NOT_IN:
                $data['value'] = array_map('trim', explode(',', $data['value']));
                break;
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
     *
     * @return string
     */
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName,
        $parameterName
    ) {
        switch ($comparisonType) {
            case TextFilterType::TYPE_EQUAL:
                return $ds->expr()->eq($fieldName, $parameterName, true);
            case TextFilterType::TYPE_NOT_CONTAINS:
                return $ds->expr()->notLike($fieldName, $parameterName, true);
            case TextFilterType::TYPE_IN:
                return $ds->expr()->in($fieldName, $parameterName, true);
            case TextFilterType::TYPE_NOT_IN:
                return $ds->expr()->notIn($fieldName, $parameterName, true);
            case FilterUtility::TYPE_EMPTY:
                $emptyString = $ds->expr()->literal('');

                if ($this->isCompositeField($ds, $fieldName)) {
                    $fieldName = $ds->expr()->trim($fieldName);
                }

                return $ds->expr()->orX(
                    $ds->expr()->isNull($fieldName),
                    $ds->expr()->eq($fieldName, $emptyString)
                );
            case FilterUtility::TYPE_NOT_EMPTY:
                $emptyString = $ds->expr()->literal('');

                if ($this->isCompositeField($ds, $fieldName)) {
                    $fieldName = $ds->expr()->trim($fieldName);
                }

                return $ds->expr()->andX(
                    $ds->expr()->isNotNull($fieldName),
                    $ds->expr()->neq($fieldName, $emptyString)
                );
            default:
                return $ds->expr()->like($fieldName, $parameterName, true);
        }
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param string                           $fieldName
     *
     * @return bool
     */
    protected function isCompositeField(FilterDatasourceAdapterInterface $ds, $fieldName)
    {
        $filter = $ds->getFieldByAlias($fieldName);
        if ($filter === null) {
            return false;
        }
        return (bool)preg_match('/(?<![\w:.])(CONCAT)\s*\(/im', $filter);
    }

    protected function setCaseSensitivity(FilterDatasourceAdapterInterface $ds)
    {
        $platform = $ds->getDatabasePlatform();
        if ($platform instanceof PostgreSQL92Platform && $this->isCaseInsensitive()) {
            $ds->expr()->setCaseInsensitive(true);
        }
    }

    protected function resetCaseSensitivity(FilterDatasourceAdapterInterface $ds)
    {
        $ds->expr()->setCaseInsensitive(false);
    }

    /**
     * @return bool
     */
    protected function isCaseInsensitive()
    {
        // if param doesn't exists it means case insensitive search (backward compatibility)
        return (!$this->has(FilterUtility::CASE_INSENSITIVE_KEY) || $this->get(FilterUtility::CASE_INSENSITIVE_KEY));
    }
}
