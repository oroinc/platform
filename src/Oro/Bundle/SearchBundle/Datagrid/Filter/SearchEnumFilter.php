<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Filter;

use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Filter\EnumFilter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;
use Oro\Bundle\SearchBundle\Datagrid\Form\Type\SearchEnumFilterType;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

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
            throw new \RuntimeException('Invalid filter datasource adapter provided: ' . get_class($ds));
        }

        return $this->applyRestrictions($ds, $data);
    }

    /**
     * @param FilterDatasourceAdapterInterface $ds
     * @param array $data
     *
     * @return bool
     */
    protected function applyRestrictions(FilterDatasourceAdapterInterface $ds, array $data)
    {
        $fieldName = $this->get(FilterUtility::DATA_NAME_KEY);

        $ds->addRestriction(Criteria::expr()->in($fieldName, $data['value']), FilterUtility::CONDITION_AND);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormType()
    {
        return SearchEnumFilterType::class;
    }
}
