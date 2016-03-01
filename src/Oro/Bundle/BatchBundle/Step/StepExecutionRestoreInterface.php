<?php

namespace Oro\Bundle\BatchBundle\Step;

/**
 * An interface which should be implemented by readers, writers or processors,
 * that can be reused in child jobs and as result a step execution context can be overridden
 * during an execution of a child job and need to be restored before such objects can be used
 * by a parent job again.
 */
interface StepExecutionRestoreInterface
{
    /**
     * Restores the previous instance of the StepExecution
     */
    public function restoreStepExecution();
}
