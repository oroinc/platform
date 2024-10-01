<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
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
    #[\Override]
    public function init($name, array $params)
    {
        parent::init($name, $params);

        $this->params[FilterUtility::FRONTEND_TYPE_KEY] = 'multiselect';
    }

    #[\Override]
    public function apply(FilterDatasourceAdapterInterface $ds, $data)
    {
        if (!$ds instanceof SearchFilterDatasourceAdapter) {
            throw new UnexpectedTypeException($ds, SearchFilterDatasourceAdapter::class);
        }
        if (isset($data['value']) && is_array($data['value'])) {
            $data['value'] = ExtendHelper::mapToEnumInternalIds($data['value']);
        }

        return $this->applyRestrictions($ds, $data);
    }

    #[\Override]
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

    #[\Override]
    protected function getFormType(): string
    {
        return SearchEnumFilterType::class;
    }
}
