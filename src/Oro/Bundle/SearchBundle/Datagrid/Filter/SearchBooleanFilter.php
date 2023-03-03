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
use Oro\Component\Exception\UnexpectedTypeException;

/**
 * The filter by a boolean value for a datasource based on a search index.
 */
class SearchBooleanFilter extends BooleanFilter
{
    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        $params[FilterUtility::FRONTEND_TYPE_KEY] = 'search-boolean';
        parent::init($name, $params);
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, SearchFilterDatasourceAdapter::class);
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

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType(): string
    {
        return SearchBooleanFilterType::class;
    }
}
