<?php

namespace Oro\Bundle\DataGridBundle\Datasource;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

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
