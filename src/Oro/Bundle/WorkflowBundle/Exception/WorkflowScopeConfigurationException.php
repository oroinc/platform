<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

class WorkflowScopeConfigurationException extends \RuntimeException
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        parent::__construct('Workflow Scope configuration error: ' . $message, $code, $previous);
    }
}
