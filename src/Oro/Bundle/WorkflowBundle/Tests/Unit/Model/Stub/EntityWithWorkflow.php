<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

class EntityWithWorkflow
{
    /**
     * @var WorkflowItem
     */
    protected $workflowItem;

    /**
     * @var WorkflowStep
     */
    protected $workflowStep;

    /**
     * @param WorkflowItem $workflowItem
     * @return EntityWithWorkflow
     */
    public function setWorkflowItem($workflowItem)
    {
        $this->workflowItem = $workflowItem;

        return $this;
    }

    /**
     * @return WorkflowItem
     */
    public function getWorkflowItem()
    {
        return $this->workflowItem;
    }

    /**
     * @param WorkflowItem $workflowStep
     * @return EntityWithWorkflow
     */
    public function setWorkflowStep($workflowStep)
    {
        $this->workflowStep = $workflowStep;

        return $this;
    }

    /**
     * @return WorkflowStep
     */
    public function getWorkflowStep()
    {
        return $this->workflowStep;
    }
}
