<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when an error occurs while importing workflow configuration from external sources.
 *
 * This exception indicates that the workflow configuration import process failed,
 * typically due to invalid configuration format or missing required files.
 */
class WorkflowConfigurationImportException extends \RuntimeException
{
    /**
     * @param string $message
     * @param \Throwable|null $previous
     */
    public function __construct($message, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
