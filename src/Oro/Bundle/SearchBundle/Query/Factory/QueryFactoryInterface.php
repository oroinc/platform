<?php

namespace Oro\Bundle\SearchBundle\Query\Factory;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

interface QueryFactoryInterface
{
    /**
     * Creating the Query wrapper object in the given
     * Datasource context.
     *
     * @param DatagridInterface $grid
     * @param array             $config
     * @return mixed
     */
    public function create(DatagridInterface $grid, array $config);
}
