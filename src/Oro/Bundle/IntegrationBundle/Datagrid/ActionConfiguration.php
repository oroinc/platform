<?php

namespace Oro\Bundle\IntegrationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel as Integration;
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

            $editMode = $record->getValue('editMode');

            if (EditModeUtils::isSwitchEnableAllowed($editMode)) {
                if ($record->getValue('enabled') === 'disabled') {
                    $result['deactivate'] = false;
                } else {
                    $result['activate'] = false;
                }
            } else {
                $result['activate'] = false;
                $result['deactivate'] = false;
            }
            $result['delete'] = EditModeUtils::isEditAllowed($editMode);

            return $result;
        };
    }
}
