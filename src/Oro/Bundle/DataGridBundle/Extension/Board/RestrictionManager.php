<?php

namespace Oro\Bundle\DataGridBundle\Extension\Board;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Tools\GridConfigurationHelper;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class RestrictionManager
{
    /** @var UserAgentProvider */
    protected $userAgentProvider;

    /** @var WorkflowRegistry */
    protected $workflowRegistry;

    /** @var GridConfigurationHelper */
    protected $gridConfigurationHelper;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param UserAgentProvider $userAgentProvider
     * @param GridConfigurationHelper $gridConfigurationHelper
     */
    public function __construct(
        WorkflowRegistry $workflowRegistry,
        UserAgentProvider $userAgentProvider,
        GridConfigurationHelper $gridConfigurationHelper
    ) {
        $this->workflowRegistry = $workflowRegistry;
        $this->userAgentProvider = $userAgentProvider;
        $this->gridConfigurationHelper = $gridConfigurationHelper;
    }

    /**
     * Board view is enabled only for desktops and if no workflow is enabled for entity
     *
     * @param DatagridConfiguration $config
     * @return bool
     */
    public function boardViewEnabled(DatagridConfiguration $config)
    {
        if ($config->getDatasourceType() !== OrmDatasource::TYPE) {
            return false;
        }

        $entityName = $this->gridConfigurationHelper->getEntity($config);

        return $this->userAgentProvider->getUserAgent()->isDesktop() &&
            (!$entityName || $this->workflowRegistry->getActiveWorkflowsByEntityClass($entityName)->isEmpty());
    }
}
