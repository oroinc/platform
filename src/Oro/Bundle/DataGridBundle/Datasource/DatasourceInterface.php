<?php

namespace Oro\Bundle\DataGridBundle\Datasource;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

/**
 * Defines the contract for datagrid datasources.
 *
 * Datasources are responsible for providing data to datagrids. Implementations can fetch data
 * from various sources such as ORM queries, arrays, search indexes, or external APIs. Each
 * datasource processes the datagrid configuration and returns results in a standardized format.
 */
interface DatasourceInterface
{
    /**
     * Add source to datagrid
     */
    public function process(DatagridInterface $grid, array $config);

    /**
     * Returns data extracted via datasource
     *
     * @return ResultRecordInterface[]
     */
    public function getResults();
}
