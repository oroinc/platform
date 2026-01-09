<?php

namespace Oro\Bundle\WorkflowBundle\Model\Tools;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Manages a collection of entities associated with started workflows.
 *
 * This class maintains a mapping of workflow names to their associated entities,
 * allowing for tracking of which entities have been processed by specific workflows.
 * It provides methods to add, remove, and query entities for a given workflow.
 */
class StartedWorkflowsBag
{
    /** @var ArrayCollection */
    protected $startedWorkflows;

    public function __construct()
    {
        $this->startedWorkflows = new ArrayCollection();
    }

    /**
     * @param string $workflowName
     * @param object $entity
     */
    public function addWorkflowEntity($workflowName, $entity)
    {
        $this->startedWorkflows->set(
            $workflowName,
            array_merge($this->getEntities($workflowName), [$entity])
        );
    }

    /**
     * @param string $workflowName
     *
     * @return array
     */
    public function getWorkflowEntities($workflowName)
    {
        return $this->getEntities($workflowName);
    }

    /**
     * @param string $workflowName
     * @return bool
     */
    public function hasWorkflowEntity($workflowName)
    {
        return (bool) $this->getEntities($workflowName);
    }

    /**
     * @param string $workflowName
     * @param object $entity
     */
    public function removeWorkflowEntity($workflowName, $entity)
    {
        $entities = $this->getEntities($workflowName);
        foreach ($entities as $key => $expectedEntity) {
            if ($expectedEntity === $entity) {
                unset($entities[$key]);
                break;
            }
        }

        $this->startedWorkflows->set($workflowName, $entities);
    }

    /**
     * @param string $workflowName
     */
    public function removeWorkflow($workflowName)
    {
        $this->startedWorkflows->set($workflowName, []);
    }

    /**
     * @param string $workflowName
     * @return array
     */
    protected function getEntities($workflowName)
    {
        return $this->startedWorkflows->containsKey($workflowName) ?
            $this->startedWorkflows->get($workflowName) :
            [];
    }
}
