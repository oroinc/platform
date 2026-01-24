<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

/**
 * Thrown when a workflow transition trigger configuration fails verification.
 *
 * This exception indicates that a transition trigger configuration is invalid or
 * incompatible with the workflow definition, preventing the trigger from being used.
 */
class TransitionTriggerVerifierException extends \InvalidArgumentException
{
    /**
     * @param string $message
     * @param \Exception|null $previous
     */
    public function __construct($message, ?\Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
