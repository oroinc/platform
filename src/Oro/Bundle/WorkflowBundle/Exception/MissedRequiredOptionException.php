<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when a required configuration option is missing from a workflow definition.
 *
 * This exception indicates that a mandatory option required for workflow assembly or
 * execution is not provided in the workflow configuration.
 */
class MissedRequiredOptionException extends WorkflowException
{
}
