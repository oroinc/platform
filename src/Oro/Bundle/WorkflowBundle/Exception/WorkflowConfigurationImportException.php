<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

class WorkflowConfigurationImportException extends \RuntimeException
{
    /**
     * @param string $message
     * @param \Throwable|null $previous
     */
    public function __construct($message, \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
