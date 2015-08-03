<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\DictionaryFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;

class DictionaryFilter extends AbstractFilter
{
    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $type =  $data['type'];
        $parameterName = $ds->generateParameterName($this->getName());

        $this->applyFilterToClause(
            $ds,
            $this->buildComparisonExpr(
                $ds,
                $type,
                $this->get(FilterUtility::DATA_NAME_KEY),
                $parameterName
            )
        );

        if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])) {
            $ds->setParameter($parameterName, $data['value']);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return DictionaryFilterType::NAME;
    }

    /**
     * @param mixed $data
     *
     * @return array|bool
     */
    protected function parseData($data)
    {
        $type = isset($data['type']) ? $data['type'] : null;
        if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY])
            && (!is_array($data) || !array_key_exists('value', $data) || empty($data['value']))
        ) {
            return false;
        }

        if (count($data['value']) === 1) {
            switch($type) {
                case DictionaryFilterType::TYPE_NOT_IN:
                    $type = DictionaryFilterType::NOT_EQUAL;
                    break;
                case DictionaryFilterType::TYPE_IN:
                    $type = DictionaryFilterType::EQUAL;
                    break;
            }
        }


        $data['type']  = $type;
        $data['value'] = $this->parseValue($data['type'], $data['value']);

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
                break;
            case DictionaryFilterType::EQUAL:
                return $ds->expr()->eq($fieldName, $parameterName, true);
                break;
            case DictionaryFilterType::NOT_EQUAL:
                return $ds->expr()->neq($fieldName, $parameterName, true);
                break;
            default:
                return $ds->expr()->in($fieldName, $parameterName, true);
                break;
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
        return (bool)preg_match('/(?<![\w:.])(CONCAT)\s*\(/im', $ds->getFieldByAlias($fieldName));
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
        $value = count($value == 1) ? $value[0] : $value;

        return $value;
    }
}
