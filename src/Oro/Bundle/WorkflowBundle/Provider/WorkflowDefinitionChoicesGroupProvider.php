<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\Repository\WorkflowDefinitionRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowDefinitionChoicesGroupProvider
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return array
     */
    public function getActiveGroupsChoices()
    {
        $workflowDefinitions = $this->getRepository()->findAll();

        if (!$workflowDefinitions) {
            return [];
        }

        $activeGroups = array_map(
            function (WorkflowDefinition $workflowDefinition) {
                $groups = $workflowDefinition->getExclusiveActiveGroups();
                return $this->buildChoicesFromArray($groups);
            },
            $workflowDefinitions
        );

        return call_user_func_array('array_merge', $activeGroups);
    }

    /**
     * @return array
     */
    public function getRecordGroupsChoices()
    {
        $workflowDefinitions = $this->getRepository()->findAll();

        if (!$workflowDefinitions) {
            return [];
        }

        $recordGroups = array_map(
            function (WorkflowDefinition $workflowDefinition) {
                $groups = $workflowDefinition->getExclusiveRecordGroups();
                return $this->buildChoicesFromArray($groups);
            },
            $workflowDefinitions
        );

        return call_user_func_array('array_merge', $recordGroups);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    private function buildChoicesFromArray(array $data)
    {
        $groups = [];
        foreach ($data as $groupKey) {
            $groups[$groupKey] = $groupKey;
        }

        return $groups;
    }

    /**
     * @return WorkflowDefinitionRepository
     */
    private function getRepository()
    {
        return $this->managerRegistry
            ->getManagerForClass(WorkflowDefinition::class)
            ->getRepository(WorkflowDefinition::class);
    }
}
