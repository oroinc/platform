<?php

namespace Oro\Bundle\FilterBundle\Grid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;

/**
 * Applies filters to orm datasource.
 * {@inheritDoc}
 */
class OrmFilterExtension extends AbstractFilterExtension
{
    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return
            parent::isApplicable($config)
            && $config->isOrmDatasource()
            && null !== $config->offsetGetByPath(Configuration::COLUMNS_PATH);
    }

    /**
     * {@inheritDoc}
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $filters = $this->getFiltersToApply($config);
        $filtersState = $this->filtersStateProvider->getStateFromParameters($config, $this->getParameters());

        /** @var OrmDatasource $datasource */
        $countQb = $datasource->getCountQb();
        $countQbAdapter = $countQb ? new OrmFilterDatasourceAdapter($countQb) : null;
        $datasourceAdapter = new OrmFilterDatasourceAdapter($datasource->getQueryBuilder());

        foreach ($filters as $filter) {
            $value = $filtersState[$filter->getName()] ?? null;
            if ($value === null) {
                continue;
            }

            $filterForm = $this->submitFilter($filter, $value);
            if (!$filterForm->isValid()) {
                continue;
            }

            $data = $filterForm->getData();

            // Initially added in AEIV-405 to make work date interval filters.
            if (isset($value['value']['start'])) {
                $data['value']['start_original'] = $value['value']['start'];
            }

            if (isset($value['value']['end'])) {
                $data['value']['end_original'] = $value['value']['end'];
            }

            // Applies filter to datasource.
            $filter->apply($datasourceAdapter, $data);

            // Applies filter to count query of datasource, if any.
            if ($countQbAdapter) {
                $filter->apply($countQbAdapter, $data);
            }
        }
    }
}
