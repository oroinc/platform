<?php

namespace Oro\Bundle\EntityBundle\Grid;

use Oro\Bundle\DataGridBundle\Datagrid\Datagrid;

class CustomEntityDatagrid extends Datagrid
{
    /**
     * {@inheritdoc}
     */
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
