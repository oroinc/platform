<?php

namespace Oro\Bundle\SearchBundle\Datagrid\Extension\Filter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\FilterBundle\Grid\Extension\AbstractFilterExtension;
use Oro\Bundle\FilterBundle\Grid\Extension\Configuration;
use Oro\Bundle\SearchBundle\Datagrid\Datasource\SearchDatasource;
use Oro\Bundle\SearchBundle\Datagrid\Filter\Adapter\SearchFilterDatasourceAdapter;

/**
 * Applies filters to search datasource.
 * {@inheritDoc}
 */
class SearchFilterExtension extends AbstractFilterExtension
{
    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && SearchDatasource::TYPE === $config->getDatasourceType()
            && null !== $config->offsetGetByPath(Configuration::COLUMNS_PATH);
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        if (!$datasource instanceof SearchDatasource) {
            throw new \InvalidArgumentException('Datasource should be an instance of SearchDatasource.');
        }

        $datasourceAdapter = new SearchFilterDatasourceAdapter($datasource->getSearchQuery());
        $filters = $this->getFiltersToApply($config);
        $filtersState = $this->filtersStateProvider->getStateFromParameters($config, $this->getParameters());

        foreach ($filters as $filter) {
            $value = $filtersState[$filter->getName()] ?? null;
            if ($value === null) {
                continue;
            }

            $filterForm = $this->submitFilter($filter, $value);
            if ($filterForm->isValid()) {
                $filter->apply($datasourceAdapter, $filterForm->getData());
            }
        }
    }
}
