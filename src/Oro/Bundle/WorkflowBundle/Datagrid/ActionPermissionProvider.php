<?php

namespace Oro\Bundle\WorkflowBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;

class ActionPermissionProvider
{
    /**
     * @var ConfigProviderInterface
     */
    protected $configProvider;

    /**
     * @param ConfigProviderInterface $configProvider
     */
    public function __construct(ConfigProviderInterface $configProvider)
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
            $isActiveWorkflow = $record->getValue('name') == $config->get('active_workflow');
        }

        $isSystem = $record->getValue('system');

        return array(
            'update' => !$isSystem,
            'clone'  => true,
            'delete' => !$isSystem,
            'activate' => !$isActiveWorkflow,
            'deactivate' => $isActiveWorkflow
        );
    }
}
