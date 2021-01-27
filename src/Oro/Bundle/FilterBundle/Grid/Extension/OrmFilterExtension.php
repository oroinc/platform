<?php

namespace Oro\Bundle\FilterBundle\Grid\Extension;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;

/**
 * Applies filters to an ORM datasource.
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
        $countDs = $countQb ? new OrmFilterDatasourceAdapter($countQb) : null;
        $ds = new OrmFilterDatasourceAdapter($datasource->getQueryBuilder());

        $this->filterExecutionContext->enableValidation();
        try {
            foreach ($filters as $filter) {
                $data = $filtersState[$filter->getName()] ?? null;
                if (null === $data) {
                    continue;
                }

                $filterForm = $this->submitFilter($filter, $data);
                if (!$filterForm->isValid()) {
                    continue;
                }

                $normalizedData = $filterForm->getData();
                $filter->apply($ds, $normalizedData);
                if (null !== $countDs) {
                    $filter->apply($countDs, $normalizedData);
                }
            }
        } finally {
            $this->filterExecutionContext->disableValidation();
        }
    }
}
