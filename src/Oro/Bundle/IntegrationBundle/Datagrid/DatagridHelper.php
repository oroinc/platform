<?php

namespace Oro\Bundle\IntegrationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class DatagridHelper
{
    /**
     * Hide delete channel action on grid
     *
     * @return callable
     */
    public function getActionConfigurationClosure()
    {
        return function (ResultRecordInterface $record) {
            if ($record->getValue('syncCount') > 0) {
                return ['delete' => false];
            }
        };
    }
}
