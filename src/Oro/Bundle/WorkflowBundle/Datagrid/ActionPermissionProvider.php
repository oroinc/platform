<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowSystemConfigManager;

class ActionPermissionProvider
{
    /**
     * @var WorkflowSystemConfigManager
     */
    protected $configManager;

    /**
     * @param WorkflowSystemConfigManager $configManager
     */
    public function __construct(WorkflowSystemConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function getWorkflowDefinitionPermissions(ResultRecordInterface $record)
    {
        $relatedEntity = $record->getValue('entityClass');
        
        $activeWorkflows = $this->configManager->getActiveWorkflowNamesByEntity($relatedEntity);
        
        $isActiveWorkflow = in_array($record->getValue('name'), $activeWorkflows, true);

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
