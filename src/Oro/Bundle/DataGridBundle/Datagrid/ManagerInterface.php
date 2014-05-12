<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

interface ManagerInterface
{
    /**
     * Returns prepared datagrid object for further operations
     *
     * @param string $name
     * @param ParameterBag|array $parameters
     * @return DatagridInterface
     */
    public function getDatagrid($name, $parameters = null);

    /**
     * Returns prepared datagrid object for further operations based on parameters of request
     *
     * @param string $name
     * @param array $additionalParameters
     * @return DatagridInterface
     */
    public function getDatagridByRequestParams($name, array $additionalParameters = []);

    /**
     * Returns prepared config for requested datagrid
     * Throws exception in case when datagrid configuration not found
     *
     * @param string $name
     *
     * @return DatagridConfiguration
     * @throws \RuntimeException
     */
    public function getConfigurationForGrid($name);
}
