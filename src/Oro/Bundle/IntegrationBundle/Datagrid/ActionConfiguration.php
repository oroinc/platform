<?php

namespace Oro\Bundle\IntegrationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class ActionConfiguration
{
    /**
     * @return callable
     */
    public static function getIsSyncAvailableCondition()
    {
        return function (ResultRecordInterface $record) {
            if (!$record->getValue('enabled')) {
                return ['schedule' => false];
            }
        };
    }
}
