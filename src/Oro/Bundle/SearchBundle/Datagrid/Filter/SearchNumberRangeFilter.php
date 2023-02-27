<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

/**
 * The filter by a numeric value or a range of numeric values for a datasource based on a search index.
 */
class SearchNumberRangeFilter extends SearchNumberFilter
{
    /**
     * {@inheritDoc}
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

                if (null !== $valueEnd) {
                    $ds->addRestriction($builder->lte($fieldName, $valueEnd), FilterUtility::CONDITION_AND);
                }

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
     * {@inheritDoc}
     */
    protected function getFormType(): string
    {
        return NumberRangeFilterType::class;
    }
}
