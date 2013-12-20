<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

class BooleanFilter extends ChoiceFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return BooleanFilterType::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function init($name, array $params)
    {
        // static option for metadata
        $params['contextSearch'] = false;
        parent::init($name, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $this->applyFilterToClause(
            $ds,
            $this->buildComparisonExpr(
                $ds,
                $data['value'],
                $this->get(FilterUtility::DATA_NAME_KEY),
                null
            )
        );

        return true;
    }

    /**
     * @param mixed $data
     *
     * @return array|bool
     */
    public function parseData($data)
    {
        $allowedValues = array(BooleanFilterType::TYPE_YES, BooleanFilterType::TYPE_NO);
        if (!is_array($data)
            || !array_key_exists('value', $data)
            || !$data['value']
            || !in_array($data['value'], $allowedValues)
        ) {
            return false;
        }

        return $data;
    }

    /**
     * Build an expression used to filter data
     *
     * @param FilterDatasourceAdapterInterface $ds
     * @param int                              $comparisonType 0 to compare with false, 1 to compare with true
     * @param string                           $fieldName
     * @param string                           $parameterName  Not used in this type of a filter
     * @return string
     */
    protected function buildComparisonExpr(
        FilterDatasourceAdapterInterface $ds,
        $comparisonType,
        $fieldName,
        $parameterName
    ) {
        switch ($comparisonType) {
            case BooleanFilterType::TYPE_YES:
                return $ds->expr()->eq($fieldName, 'true');
            default:
                return $ds->expr()->neq($fieldName, 'true');
        }
    }
}
