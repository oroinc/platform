<?php

namespace Oro\Bundle\DataGridBundle\Datasource;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

/**
 * Binds parameters of datagrid to it's datasource
 */
interface ParameterBinderInterface
{
    /**
     * Binds datagrid parameters to datasource.
     *
     * Example of usage:
     * <code>
     *  // get parameter "name" from datagrid parameter bag and add it to datasource
     *  $queryParameterBinder->bindParameters($datagrid, ['name']);
     *
     *  // get parameter "id" from datagrid parameter bag and add it to datasource as parameter "client_id"
     *  $queryParameterBinder->bindParameters($datagrid, ['client_id' => 'id']);
     *
     *  // get parameter "email" from datagrid parameter bag and add it to datasource, all other existing
     *  // parameters will be cleared
     *  $queryParameterBinder->bindParameters($datagrid, ['email'], false);
     * </code>
     *
     *
     * @param DatagridInterface $datagrid
     * @param array $datasourceToDatagridParameters
     * @param bool $append
     */
    public function bindParameters(
        DatagridInterface $datagrid,
        array $datasourceToDatagridParameters,
        $append = true
    );
}
