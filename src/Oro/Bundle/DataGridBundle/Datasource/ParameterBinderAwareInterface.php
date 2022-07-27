<?php

namespace Oro\Bundle\DataGridBundle\Datasource;

/**
 * Data sources that supports parameter binding must implement this interface.
 */
interface ParameterBinderAwareInterface
{
    /**
     * Binds datagrid parameters to datasource query.
     *
     * @see ParameterBinderInterface::bindParameters
     * @param array $datasourceToDatagridParameters
     * @param bool $append
     */
    public function bindParameters(array $datasourceToDatagridParameters, $append = true);
}
