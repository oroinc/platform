<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when a requested workflow definition cannot be found in the system.
 *
 * This exception indicates that an attempt was made to access a workflow by name
 * that does not exist or has not been registered.
 */
class WorkflowNotFoundException extends WorkflowException
{
    public function __construct($name)
    {
        parent::__construct(sprintf('Workflow "%s" not found', $name));
    }
}
