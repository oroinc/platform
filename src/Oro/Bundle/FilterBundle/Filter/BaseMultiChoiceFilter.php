<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

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

        return $metadata;
    }

    /**
     * Return a value depending on comparison type
     *
     * @param string $value
     *
     * @return mixed
     */
    protected function parseValue($value)
    {
        return count($value) === 1 ? $value[0] : $value;
    }

    /**
     * @param mixed $data
     *
     * @return array|bool
     */
    protected function parseData($data)
    {
        $type = array_key_exists('type', $data) ? $data['type'] : null;
        if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY], true)
            && (!is_array($data) || !array_key_exists('value', $data) || empty($data['value']))
        ) {
            return false;
        }

        if (count($data['value']) === 1) {
            switch ($type) {
                case DictionaryFilterType::TYPE_NOT_IN:
                    $type = DictionaryFilterType::NOT_EQUAL;
                    break;
                case DictionaryFilterType::TYPE_IN:
                    $type = DictionaryFilterType::EQUAL;
                    break;
            }
        }

        $data['type']  = $type;
        $data['value'] = $this->parseValue($data['value']);

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
