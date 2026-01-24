<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when a workflow attribute is referenced but does not exist in the workflow definition.
 *
 * This exception indicates that an attempt was made to access or manipulate a workflow
 * attribute that is not defined in the current workflow.
 */
class UnknownAttributeException extends WorkflowException
{
}
