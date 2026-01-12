<?php

namespace Oro\Bundle\WorkflowBundle\Model;

/**
 * Defines the contract for objects that are aware of and can manage a specific workflow.
 *
 * Implementations can retrieve and set the workflow name they are associated with,
 * enabling workflow-specific operations and context management.
 */
interface WorkflowAwareInterface
{
    /** @return string */
    public function getWorkflowName();

    /** @param string $workflowName */
    public function setWorkflowName($workflowName);
}
