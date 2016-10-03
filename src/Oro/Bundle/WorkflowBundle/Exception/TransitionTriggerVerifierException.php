<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

class TransitionTriggerVerifierException extends \InvalidArgumentException
{
    /**
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($message, \Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
