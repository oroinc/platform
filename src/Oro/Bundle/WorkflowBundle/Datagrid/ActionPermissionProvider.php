<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Configuration\Checker\ConfigurationChecker;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;

class ActionPermissionProvider
{
    /** @var FeatureChecker */
    private $featureChecker;

    /** @var ConfigurationChecker */
    private $configurationChecker;

    /**
     * @param FeatureChecker $featureChecker
     * @param ConfigurationChecker $configurationChecker
     */
    public function __construct(FeatureChecker $featureChecker, ConfigurationChecker $configurationChecker)
    {
        $this->featureChecker = $featureChecker;
        $this->configurationChecker = $configurationChecker;
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function getWorkflowDefinitionPermissions(ResultRecordInterface $record)
    {
        $isFeatureEnabled = $this->featureChecker->isResourceEnabled(
            $record->getValue('name'),
            FeatureConfigurationExtension::WORKFLOWS_NODE_NAME
        );
        $isConfigurationValid = $this->configurationChecker->isClean($record->getValue('configuration'));
        $isActiveWorkflow = $record->getValue('active');
        $isSystem = $record->getValue('system');

        return [
            'activate'   => $isFeatureEnabled && !$isActiveWorkflow,
            'clone'      => $isConfigurationValid,
            'deactivate' => $isFeatureEnabled && $isActiveWorkflow,
            'delete'     => !$isSystem,
            'update'     => $isFeatureEnabled && $isConfigurationValid && !$isSystem && !$isActiveWorkflow,
            'view'       => true,
        ];
    }

    /**
     * @param ResultRecordInterface $record
     * @return array
     */
    public function getProcessDefinitionPermissions(ResultRecordInterface $record)
    {
        $isFeatureEnabled = $this->featureChecker->isResourceEnabled(
            $record->getValue('name'),
            FeatureConfigurationExtension::PROCESSES_NODE_NAME
        );
        $isEnabled = $record->getValue('enabled');
        return [
            'activate'   => $isFeatureEnabled && !$isEnabled,
            'deactivate' => $isFeatureEnabled && $isEnabled,
            'view'       => true,
        ];
    }
}
