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
        $isSystem = $record->getValue('system');

        return array(
            'update' => !$isSystem,
            'clone'  => true,
            'delete' => !$isSystem,
        );
    }
}
