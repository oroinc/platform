<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;

class SearchMultiEnumFilter extends SearchEnumFilter
{
    /**
     * {@inheritDoc}
     */
    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $data)
    {
        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);
        $criteria = Criteria::create();
        $builder = Criteria::expr();
        $placeholder = new EnumIdPlaceholder();

        foreach ($data['value'] as $value) {
            $criteria->orWhere(
                $builder->eq(
                    $placeholder->replace($fieldName, [EnumIdPlaceholder::NAME => $value]),
                    1
                )
            );
        }

        $ds->addRestriction($criteria->getWhereExpression(), FilterUtility::CONDITION_AND);

        return true;
    }
}
