<?php

namespace Oro\Bundle\WorkflowBundle\Exception;

use Exception;

class TransitionTriggerVerifierException extends \InvalidArgumentException
{
    public function __construct($message, Exception $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
