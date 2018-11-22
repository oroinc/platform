<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\BooleanAttributeType;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\BooleanFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchBooleanFilterType;
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
        return SearchBooleanFilterType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new \RuntimeException('Invalid filter datasource adapter provided: '.get_class($ds));
        }

        if (!isset($data['value']) || !is_array($data['value'])) {
            return;
        }

        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);
        $builder = Criteria::expr();

        $values = [];
        foreach ($data['value'] as $value) {
            switch ($value) {
                case BooleanFilterType::TYPE_YES:
                    $values[] = BooleanAttributeType::TRUE_VALUE;
                    break;
                case BooleanFilterType::TYPE_NO:
                    $values[] = BooleanAttributeType::FALSE_VALUE;
                    break;
            }
        }

        if (empty($values)) {
            return;
        }

        $ds->addRestriction(
            $builder->in($fieldName, $values),
            FilterUtility::CONDITION_AND
        );
    }
}
