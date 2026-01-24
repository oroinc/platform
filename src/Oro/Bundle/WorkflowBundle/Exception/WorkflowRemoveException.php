<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when a system workflow cannot be removed.
 *
 * This exception indicates that an attempt was made to delete a workflow that is marked
 * as a system workflow and therefore cannot be removed.
 */
class WorkflowRemoveException extends WorkflowException
{
    public function __construct($workflowName, $code = 0, ?\Exception $previous = null)
    {
        parent::__construct(
            sprintf("Workflow '%s' can't be removed due its System workflow", $workflowName),
            $code,
            $previous
        );
    }
}
