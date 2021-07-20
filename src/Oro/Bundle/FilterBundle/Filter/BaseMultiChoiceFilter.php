<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;

/**
 * The base class for filters that support multi choices.
 */
abstract class BaseMultiChoiceFilter extends AbstractFilter
{
    /**
     * {@inheritdoc}
     */
    protected $joinOperators = [
        FilterUtility::TYPE_NOT_EMPTY => FilterUtility::TYPE_EMPTY,
        DictionaryFilterType::NOT_EQUAL => DictionaryFilterType::EQUAL,
        DictionaryFilterType::TYPE_NOT_IN => DictionaryFilterType::TYPE_IN,
    ];

    /**
     * @return array
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        if (isset($this->params['options']['class'])) {
            $metadata['class'] = $this->params['options']['class'];
        }

        if (array_key_exists('dictionaryValueRoute', $this->params)) {
            $metadata['dictionaryValueRoute'] = $this->params['dictionaryValueRoute'];
        }

        if (array_key_exists('dictionaryValueSearch', $this->params)) {
            $metadata['dictionarySearchRoute'] = $this->params['dictionarySearchRoute'];
        }

        return $metadata;
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

        $value = $data['value'];
        if (\is_array($value) && count($value) === 1) {
            $value = reset($value);
            $data['value'] = $value;
        }

        $data = $this->parseComparisonType($data);

        return $data;
    }

    protected function parseComparisonType(array $data): array
    {
        $type = $data['type'];
        if ($type) {
            $type = (int)$type;
            if (\is_array($data['value'])) {
                if (DictionaryFilterType::EQUAL === $type) {
                    $type = DictionaryFilterType::TYPE_IN;
                } elseif (DictionaryFilterType::NOT_EQUAL === $type) {
                    $type = DictionaryFilterType::TYPE_NOT_IN;
                }
            } elseif (DictionaryFilterType::TYPE_IN === $type) {
                $type = DictionaryFilterType::EQUAL;
            } elseif (DictionaryFilterType::TYPE_NOT_IN === $type) {
                $type = DictionaryFilterType::NOT_EQUAL;
            }
        } else {
            $type = \is_array($data['value'])
                ? DictionaryFilterType::TYPE_IN
                : DictionaryFilterType::EQUAL;
        }
        $data['type'] = $type;

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
     * @return mixed
     */
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName,
        $parameterName
    ) {
        switch ($comparisonType) {
            case DictionaryFilterType::TYPE_NOT_IN:
                return $ds->expr()->notIn($fieldName, $parameterName, true);
            case DictionaryFilterType::EQUAL:
                return $ds->expr()->eq($fieldName, $parameterName, true);
            case DictionaryFilterType::NOT_EQUAL:
                return $ds->expr()->neq($fieldName, $parameterName, true);
            default:
                return $ds->expr()->in($fieldName, $parameterName, true);
        }
    }
}
