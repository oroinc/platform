<?php

namespace Oro\Bundle\FilterBundle\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

class NumberFilter extends AbstractFilter
{
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
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        $data = $this->parseData($data);
        if (!$data) {
            return false;
        }

        $parameterName = $ds->generateParameterName($this->getName());

        $this->applyFilterToClause(
            $ds,
            $this->buildComparisonExpr(
                $ds,
                $data['type'],
                $this->get(FilterUtility::DATA_NAME_KEY),
                $parameterName
            )
        );

        $ds->setParameter($parameterName, $data['value']);

        return true;
    }

    /**
     * @param mixed $data
     *
     * @return array|bool
     */
    public function parseData($data)
    {
        if (!is_array($data) || !array_key_exists('value', $data) || !is_numeric($data['value'])) {
            return false;
        }

        $data['type'] = isset($data['type']) ? $data['type'] : null;

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
            default:
                return $ds->expr()->eq($fieldName, $parameterName, true);
        }
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
