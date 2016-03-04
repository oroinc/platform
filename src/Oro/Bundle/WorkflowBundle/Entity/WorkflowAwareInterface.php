<?php

namespace Oro\Bundle\WorkflowBundle\Entity;

interface WorkflowAwareInterface
{
    /**
     * @param  WorkflowItem $workflowItem
     * @return $this
     */
    public function setWorkflowItem(WorkflowItem $workflowItem = null);

    /**
     * @return WorkflowItem
     */
    public function getWorkflowItem();

    /**
     * @param  WorkflowStep $workflowStep
     * @return $this
     */
    public function setWorkflowStep(WorkflowStep $workflowStep = null);

    /**
     * @return WorkflowStep
     */
    public function getWorkflowStep();
}
