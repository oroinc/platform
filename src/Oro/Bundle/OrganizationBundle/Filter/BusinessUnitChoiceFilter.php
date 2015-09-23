<?php

namespace Oro\Bundle\OrganizationBundle\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;

use Oro\Bundle\FilterBundle\Filter\ChoiceFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;

class BusinessUnitChoiceFilter extends ChoiceFilter
{
    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        $metadata = parent::getMetadata();
        $metadata[FilterUtility::TYPE_KEY] = 'choice-business-unit';

        return $metadata;
    }

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

        if (!in_array($type, [FilterUtility::TYPE_EMPTY, FilterUtility::TYPE_NOT_EMPTY], true)) {
            $ds->setParameter($parameterName, $data['value']);
        }

        return true;
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
            default:
                return $ds->expr()->in($fieldName, $parameterName, true);
                break;
        }
    }
}
