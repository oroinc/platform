<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Exception\RuntimeException;

interface ManagerInterface
{
    /**
     * Returns prepared datagrid object for further operations.
     *
     * @param string $name Unique name of grid, optionally with scope ("grid-name:grid-scope")
     * @param ParameterBag|array|null $parameters
     * @return DatagridInterface
     */
    public function getDatagrid($name, $parameters = null);

    /**
     * Returns prepared datagrid object for further operations based on parameters from request.
     *
     * @param string $name Unique name of grid, optionally with scope ("grid-name:grid-scope")
     * @param array $additionalParameters Additional params that will be merged with request params to use in grid
     * @return DatagridInterface
     */
    public function getDatagridByRequestParams($name, array $additionalParameters = []);

    /**
     * Returns prepared config for requested datagrid
     *
     * @param string $name Unique name of grid, optionally with scope ("grid-name:grid-scope")
     * @return DatagridConfiguration
     * @throws RuntimeException If datagrid configuration not found
     */
    public function getConfigurationForGrid($name);
}
