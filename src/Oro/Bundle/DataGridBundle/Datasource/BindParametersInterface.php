<?php

namespace Oro\Bundle\DataGridBundle\Datasource;

/**
 * Data sources that supports parameter binding must implement this interface.
 */
interface BindParametersInterface
{
    /**
     * Binds datagrid parameters to datasource query.
     */
    public function bindParameters(array $datasourceToDatagridParameters, bool $append = true): void;
}
