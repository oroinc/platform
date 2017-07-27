<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\WorkflowBundle\Configuration\Checker\ConfigurationChecker;

class ActionPermissionProvider
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var ConfigurationChecker */
    protected $configurationChecker;

    /**
     * @param ConfigProvider $configProvider
     * @param ConfigurationChecker $configurationChecker
     */
    public function __construct(ConfigProvider $configProvider, ConfigurationChecker $configurationChecker)
    {
        $this->configProvider = $configProvider;
        $this->configurationChecker = $configurationChecker;
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
            $isActiveWorkflow = $record->getValue('name') == $config->get('active_workflow');
        }
        $isConfigurationValid = $this->configurationChecker->isClean($record->getValue('configuration'));
        $isSystem = $record->getValue('system');

        return array(
            'activate'   => !$isActiveWorkflow,
            'clone'      => $isConfigurationValid,
            'deactivate' => $isActiveWorkflow,
            'delete'     => !$isSystem,
            'update'     => !$isSystem && $isConfigurationValid,
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
