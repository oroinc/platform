<?php

namespace Oro\Bundle\SearchBundle\Extension\Sorter;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\SearchBundle\Datasource\SearchDatasource;

class SearchSorterExtension extends AbstractSorterExtension
{
    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        return parent::isApplicable($config) && $config->getDatasourceType() === SearchDatasource::TYPE;
    }

    protected function addSorterToDasource(array $sorter, $direction, DatasourceInterface $datasource)
    {
        $sortKey = $sorter['data_name'];

        if (array_key_exists(PropertyInterface::TYPE_KEY, $sorter)) {
            // pass type if specified
            $datasource->getQuery()->setOrderBy($sortKey, $direction, $sorter[PropertyInterface::TYPE_KEY]);
        } else {
            // otherwise use default type
            $datasource->getQuery()->setOrderBy($sortKey, $direction);
        }
    }
}
