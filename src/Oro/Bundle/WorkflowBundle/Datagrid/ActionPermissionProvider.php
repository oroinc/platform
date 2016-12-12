<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\WorkflowBundle\Configuration\FeatureConfigurationExtension;

class ActionPermissionProvider
{
    /**
     * @var FeatureChecker
     */
    private $featureChecker;

    /**
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
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
        $isActiveWorkflow = $record->getValue('active');
        $isSystem = $record->getValue('system');

        return array(
            'activate'   => $isFeatureEnabled && !$isActiveWorkflow,
            'clone'      => true,
            'deactivate' => $isFeatureEnabled && $isActiveWorkflow,
            'delete'     => !$isSystem,
            'update'     => $isFeatureEnabled && !$isSystem,
            'view'       => true,
        );
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
        return array(
            'activate'   => $isFeatureEnabled && !$isEnabled,
            'deactivate' => $isFeatureEnabled && $isEnabled,
            'view'       => true,
        );
    }
}
