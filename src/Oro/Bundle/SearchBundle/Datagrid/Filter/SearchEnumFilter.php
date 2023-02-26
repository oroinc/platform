<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\EnumFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEnumFilterType;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Component\Exception\UnexpectedTypeException;

/**
 * The filter by an enum entity for a datasource based on a search index.
 */
class SearchEnumFilter extends EnumFilter
{
    /**
     * {@inheritDoc}
     */
    public function init($name, array $params)
    {
        parent::init($name, $params);

        $this->params[FilterUtility::FRONTEND_TYPE_KEY] = 'multiselect';
    }

    /**
     * {@inheritDoc}
     */
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, SearchFilterDatasourceAdapter::class);
        }

        return $this->applyRestrictions($ds, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function prepareData(array $data): array
    {
        throw new \BadMethodCallException('Not implemented');
    }

    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $data): bool
    {
        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);

        $ds->addRestriction(Criteria::expr()->in($fieldName, $data['value']), FilterUtility::CONDITION_AND);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType(): string
    {
        return SearchEnumFilterType::class;
    }
}
