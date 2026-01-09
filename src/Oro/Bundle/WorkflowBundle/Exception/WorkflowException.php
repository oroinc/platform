<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when a workflow-related error occurs.
 *
 * This exception is raised when the workflow system encounters errors during workflow execution,
 * validation, or state transitions. It serves as the base exception for all workflow-related errors.
 */
class WorkflowException extends \Exception
{
}
