<?php

namespace Oro\Bundle\IntegrationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;

class ActionConfiguration
{
    /**
     * @return callable
     */
    public static function getIsSyncAvailableCondition()
    {
        return function (ResultRecordInterface $record) {
            $result = [];
            if ($record->getValue('enabled') === 'disabled') {
                $result['schedule'] = false;
            }

            if ($record->getValue('editMode') == Channel::EDIT_MODE_DISALLOW) {
                $result['delete'] = false;
            }

            return $result;
        };
    }
}
