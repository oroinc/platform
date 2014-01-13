<?php

namespace Oro\Bundle\BatchBundle\Step;

/**
 * Represents an interface which should be implemented by classes responsible for
 * handle warnings are occurred during a step execution.
 */
interface StepExecutionWarningHandlerInterface
{
    /**
     * Handle step execution warning
     *
     * @param object $element A step element (for example a reader or a processor) causes a warning
     * @param string $name    A warning name
     * @param string $reason  A warning reason
     * @param mixed  $item    An item processing of which caused a warning
     */
    public function handleWarning($element, $name, $reason, $item);
}
