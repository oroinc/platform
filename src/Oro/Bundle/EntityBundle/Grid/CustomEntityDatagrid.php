<?php

namespace Oro\Bundle\EntityBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;

/**
 * Datagrid for displaying custom entities.
 *
 * This datagrid extends the base Datagrid class and dynamically sets the entity class
 * in the `FROM` clause based on the `class_name` parameter, enabling the display of
 * any custom entity in a datagrid.
 */
class CustomEntityDatagrid extends Datagrid
{
    #[\Override]
    public function initialize()
    {
        parent::initialize();

        // set entity to FROM part of a query
        $query = $this->config->getOrmQuery();
        $fromPart = $query->getFrom();
        $fromPart[0]['table'] = $this->parameters->get('class_name');
        $query->setFrom($fromPart);
    }
}
