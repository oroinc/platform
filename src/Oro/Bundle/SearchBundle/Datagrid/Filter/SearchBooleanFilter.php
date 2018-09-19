<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\BooleanAttributeType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\BooleanFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

/**
 * The filter model that will be used to display and apply boolean filters in the search process.
 */
class SearchBooleanFilter extends BooleanFilter
{
    /**
     * {@inheritdoc}
     */
    protected function getFormType()
    {
        return BooleanFilterType::NAME;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new \RuntimeException('Invalid filter datasource adapter provided: '.get_class($ds));
        }

        if (!isset($data['value'])) {
            return;
        }

        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);
        $builder = Criteria::expr();

        switch ($data['value']) {
            case BooleanFilterType::TYPE_YES:
                $ds->addRestriction(
                    $builder->eq($fieldName, BooleanAttributeType::TRUE_VALUE),
                    FilterUtility::CONDITION_AND
                );
                break;
            case BooleanFilterType::TYPE_NO:
                $ds->addRestriction(
                    $builder->eq($fieldName, BooleanAttributeType::FALSE_VALUE),
                    FilterUtility::CONDITION_AND
                );
                break;
        }
    }
}
