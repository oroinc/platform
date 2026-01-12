<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when a user attempts to execute a workflow transition they are not authorized to perform.
 *
 * This exception indicates that the transition is not allowed for the current user due to
 * insufficient permissions or access control restrictions.
 */
class ForbiddenTransitionException extends WorkflowException
{
}
