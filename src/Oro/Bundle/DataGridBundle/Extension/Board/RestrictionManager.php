<?php

namespace Oro\Bundle\DataGridBundle\Extension\Board;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\UIBundle\Provider\UserAgentProvider;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

class RestrictionManager
{
    /** @var UserAgentProvider */
    protected $userAgentProvider;

    /** @var WorkflowRegistry */
    protected $workflowRegistry;

    /** @var EntityClassResolver */
    protected $entityClassResolver;

    /**
     * @param WorkflowRegistry $workflowRegistry
     * @param UserAgentProvider $userAgentProvider
     * @param EntityClassResolver $entityClassResolver
     */
    public function __construct(
        WorkflowRegistry $workflowRegistry,
        UserAgentProvider $userAgentProvider,
        EntityClassResolver $entityClassResolver
    ) {
        $this->workflowRegistry = $workflowRegistry;
        $this->userAgentProvider = $userAgentProvider;
        $this->entityClassResolver = $entityClassResolver;
    }

    /**
     * Board view is enabled only for desktops and if no workflow is enabled for entity
     *
     * @param DatagridConfiguration $config
     * @return bool
     */
    public function boardViewEnabled(DatagridConfiguration $config)
    {
        if (!$config->isOrmDatasource()) {
            return false;
        }

        $entityName = $config->getOrmQuery()->getRootEntity($this->entityClassResolver, true);

        return
            $this->userAgentProvider->getUserAgent()->isDesktop()
            && (
                !$entityName
                || $this->workflowRegistry->getActiveWorkflowsByEntityClass($entityName)->isEmpty()
            );
    }
}
