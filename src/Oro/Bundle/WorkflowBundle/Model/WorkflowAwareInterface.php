<?php

namespace Oro\Bundle\WorkflowBundle\Model;

interface WorkflowAwareInterface
{
    /** @return string */
    public function getWorkflowName();

    /**
     * @param string $workflowName
     */
    public function setWorkflowName($workflowName);
}
