<?php

namespace Oro\Bundle\DataGridBundle\Datasource;

/**
 * Datasources that supports parameter binding must implement this interface.
 */
interface ParameterBinderAwareInterface
{
    /**
     * Gets parameter binder.
     *
     * @return ParameterBinderInterface
     */
    public function getParameterBinder();

    /**
     * Binds datagrid parameters to datasource query.
     *
     * @see ParameterBinderInterface::bindParameters
     * @param array $datasourceToDatagridParameters
     * @param bool $append
     */
    public function bindParameters(array $datasourceToDatagridParameters, $append = true);
}
