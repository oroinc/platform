<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when an invalid argument is provided to a workflow operation.
 *
 * This exception indicates that a required argument is missing, has an invalid type,
 * or contains an invalid value for the workflow operation being performed.
 */
class InvalidArgumentException extends \InvalidArgumentException
{
}
