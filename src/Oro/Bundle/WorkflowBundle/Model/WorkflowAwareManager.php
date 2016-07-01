<?php

namespace Oro\Bundle\WorkflowBundle\Model;

class WorkflowAwareManager implements WorkflowAwareInterface
{
    /** @var string */
    protected $workflowName;

    /** @var WorkflowManager */
    private $workflowManager;

    /**
     * @param WorkflowManager $workflowManager
     */
    public function __construct(WorkflowManager $workflowManager)
    {
        $this->workflowManager = $workflowManager;
    }

    /**
     * @param $entity
     * @return null|\Oro\Bundle\WorkflowBundle\Entity\WorkflowItem
     */
    public function getWorkflowItem($entity)
    {
        return $this->workflowManager->getWorkflowItem($entity, $this->workflowName);
    }

    public function setWorkflowName($workflowName)
    {
        $this->workflowName = $workflowName;
    }

    public function getWorkflowName()
    {
        return $this->workflowName;
    }
}
