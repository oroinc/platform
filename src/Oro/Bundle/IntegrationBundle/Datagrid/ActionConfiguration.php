<?php

namespace Oro\Bundle\IntegrationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Utils\EditModeUtils;

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

            if (EditModeUtils::isEditAllowed($record->getValue('editMode'))) {
                if ($record->getValue('enabled') === 'disabled') {
                    $result['deactivate'] = false;
                } else {
                    $result['activate'] = false;
                }
            } else {
                $result['delete'] = false;
                $result['activate'] = false;
                $result['deactivate'] = false;
            }

            return $result;
        };
    }
}
