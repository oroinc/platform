<?php

namespace Oro\Bundle\DataGridBundle\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ParameterBinderAwareInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;

/**
 * Binds datagrid parameters to datasource from datasource option "bind_parameters".
 *
 * @see \Oro\Bundle\DataGridBundle\Datasource\Orm\ParameterBinder
 */
class DatasourceBindParametersListener
{
    const DATASOURCE_BIND_PARAMETERS_PATH = '[source][bind_parameters]';

    /**
     * Binds datagrid parameters to datasource query on event.
     *
     * @param BuildAfter $event
     */
    public function onBuildAfter(BuildAfter $event)
    {
        $datagrid = $event->getDatagrid();
        $datasource = $datagrid->getDatasource();

        if (!$datasource instanceof ParameterBinderAwareInterface) {
            return;
        }

        $parameters = $datagrid->getConfig()->offsetGetByPath(self::DATASOURCE_BIND_PARAMETERS_PATH, []);

        if (!$parameters || !is_array($parameters)) {
            return;
        }

        $datasource->bindParameters($parameters);
    }
}
