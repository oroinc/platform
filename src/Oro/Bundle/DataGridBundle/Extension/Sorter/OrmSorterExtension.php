<?php

namespace Oro\Bundle\DataGridBundle\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

class OrmSorterExtension extends AbstractSorterExtension
{
    /**
     * {@inheritDoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return parent::isApplicable($config) && $config->getDatasourceType() === OrmDatasource::TYPE;
    }

    protected function addSorterToDasource(array $sorter, $direction, DatasourceInterface $datasource)
    {
        $datasource->getQueryBuilder()->addOrderBy($sorter['data_name'], $direction);
    }
}
