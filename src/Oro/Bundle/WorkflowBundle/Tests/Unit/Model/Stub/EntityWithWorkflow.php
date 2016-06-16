<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Stub;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;

class EntityWithWorkflow
{
    /**
     * @var int
     */
    protected $id;

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
     * @param WorkflowStep $workflowStep
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

    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
