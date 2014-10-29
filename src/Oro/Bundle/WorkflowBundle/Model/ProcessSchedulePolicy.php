<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

/**
 * Responsible for determine is process schedule allowed.
 *
 * @see \Oro\Bundle\WorkflowBundle\EventListener\ProcessCollectorListener::scheduleProcess
 */
interface ProcessSchedulePolicy
{
    /**
     * Is scheduling of process definition is allowed.
     *
     * @param ProcessTrigger $processTrigger
     * @param ProcessData $processData
     * @return bool
     */
    public function isScheduleAllowed(ProcessTrigger $processTrigger, ProcessData $processData);
}
