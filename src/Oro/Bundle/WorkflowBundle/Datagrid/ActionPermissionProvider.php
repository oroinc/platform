<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Configuration\Checker\ConfigurationChecker;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;

/**
 * Provides permission information for workflow-related datagrid actions.
 *
 * This provider determines which actions (activate, deactivate, clone, delete, update, view)
 * are permitted for workflow definitions based on feature toggles, configuration validity,
 * and workflow state.
 */
class ActionPermissionProvider
{
    /** @var FeatureChecker */
    private $featureChecker;

    /** @var ConfigurationChecker */
    private $configurationChecker;

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
