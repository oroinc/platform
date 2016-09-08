<?php

namespace Oro\Bundle\DataGridBundle\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;

class OrmSorterExtension extends AbstractSorterExtension
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return parent::isApplicable($config) && $config->getDatasourceType() === OrmDatasource::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    protected function addSorterToDatasource(array $sorter, $direction, DatasourceInterface $datasource)
    {
        /* @var $datasource OrmDatasource */
        $datasource->getQueryBuilder()->addOrderBy($sorter['data_name'], $direction);
    }
}
