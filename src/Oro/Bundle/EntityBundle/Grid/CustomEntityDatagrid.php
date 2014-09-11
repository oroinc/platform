<?php

namespace Oro\Bundle\EntityBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;

class CustomEntityDatagrid extends Datagrid
{
    const PATH_FROM = '[source][query][from]';

    /**
     * {@inheritdoc}
     */
    public function initialize()
    {
        parent::initialize();

        // set entity to FROM part of a query
        $from             = $this->config->offsetGetByPath(self::PATH_FROM, []);
        $from[0]['table'] = $this->parameters->get('class_name');
        $this->config->offsetSetByPath(self::PATH_FROM, $from);
    }
}
