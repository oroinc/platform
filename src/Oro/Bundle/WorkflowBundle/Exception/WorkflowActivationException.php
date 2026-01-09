<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when a workflow cannot be activated due to validation or configuration errors.
 *
 * This exception indicates that the workflow definition is invalid or does not meet
 * the requirements for activation.
 */
class WorkflowActivationException extends WorkflowException
{
}
