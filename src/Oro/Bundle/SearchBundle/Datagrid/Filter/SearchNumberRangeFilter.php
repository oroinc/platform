<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

class SearchNumberRangeFilter extends SearchNumberFilter
{
    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param array $data
     * @return bool
     */
    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $data)
    {
        $value = $data['value'];
        $valueEnd = $data['value_end'];
        $fieldName = $this->getFieldName($data);
        $builder = Criteria::expr();

        switch ($data['type']) {
            case NumberRangeFilterType::TYPE_BETWEEN:
                $ds->addRestriction($builder->gte($fieldName, $value), FilterUtility::CONDITION_AND);
                $ds->addRestriction($builder->lte($fieldName, $valueEnd), FilterUtility::CONDITION_AND);

                return true;

            case NumberRangeFilterType::TYPE_NOT_BETWEEN:
                $ds->addRestriction(
                    Criteria::create()
                        ->where($builder->lte($fieldName, $value))
                        ->orWhere($builder->gte($fieldName, $valueEnd))
                        ->getWhereExpression(),
                    FilterUtility::CONDITION_AND
                );

                return true;
        }

        return parent::applyRestrictions($ds, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return NumberRangeFilterType::class;
    }
}
