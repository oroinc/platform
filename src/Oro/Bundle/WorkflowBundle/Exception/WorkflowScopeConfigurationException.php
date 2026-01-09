<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when a workflow scope configuration is invalid or incomplete.
 *
 * This exception indicates that the workflow scope configuration contains errors or
 * is missing required settings for proper scope management.
 */
class WorkflowScopeConfigurationException extends \RuntimeException
{
    public function __construct($message = "", $code = 0, ?\Exception $previous = null)
    {
        parent::__construct('Workflow Scope configuration error: ' . $message, $code, $previous);
    }
}
