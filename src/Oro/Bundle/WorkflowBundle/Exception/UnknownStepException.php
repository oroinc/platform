<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when a workflow step is referenced but does not exist in the workflow definition.
 *
 * This exception indicates that an attempt was made to access or transition to a workflow
 * step that is not defined in the current workflow.
 */
class UnknownStepException extends WorkflowException
{
    public function __construct($stepName)
    {
        parent::__construct(sprintf('Step "%s" not found', $stepName));
    }
}
