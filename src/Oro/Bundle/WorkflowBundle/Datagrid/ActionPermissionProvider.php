<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class ActionPermissionProvider
{
    /**
     * @var ConfigProvider
     */
    protected $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function getWorkflowDefinitionPermissions(ResultRecordInterface $record)
    {
        $isActiveWorkflow = false;
        $relatedEntity = $record->getValue('entityClass');
        if ($this->configProvider->hasConfig($relatedEntity)) {
            $config = $this->configProvider->getConfig($relatedEntity);
            $isActiveWorkflow = in_array($record->getValue('name'), $config->get('active_workflows', false, []), true);
        }

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
