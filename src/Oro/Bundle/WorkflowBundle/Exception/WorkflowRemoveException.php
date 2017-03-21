<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

class WorkflowRemoveException extends WorkflowException
{
    /**
     * {@inheritDoc}
     */
    public function __construct($workflowName, $code = 0, \Exception $previous = null)
    {
        parent::__construct(
            sprintf("Workflow '%s' can't be removed due its System workflow", $workflowName),
            $code,
            $previous
        );
    }
}
