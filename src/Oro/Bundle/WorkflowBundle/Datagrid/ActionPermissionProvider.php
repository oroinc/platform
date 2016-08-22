<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;

class ActionPermissionProvider
{
    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function getWorkflowDefinitionPermissions(ResultRecordInterface $record)
    {
        $isActiveWorkflow = $record->getValue('active');
        $isSystem = $record->getValue('system');

        return array(
            'activate'   => !$isActiveWorkflow,
            'clone'      => true,
            'deactivate' => $isActiveWorkflow,
            'delete'     => !$isSystem,
            'update'     => !$isSystem,
            'view'       => true,
        );
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function getProcessDefinitionPermissions(ResultRecordInterface $record)
    {
        $isEnabled = $record->getValue('enabled');
        return array(
            'activate'   => !$isEnabled,
            'deactivate' => $isEnabled,
            'view'       => true,
        );
    }
}
