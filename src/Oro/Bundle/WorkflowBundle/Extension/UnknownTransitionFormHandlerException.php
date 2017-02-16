<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

class UnknownTransitionFormHandlerException extends \LogicException
{
    public function __construct($alias)
    {
        $message = sprintf('Unknown transition form handler with alias `%s`.', $alias);
        parent::__construct($message);
    }
}
