<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Event\ProcessHandleEvent;

/**
 * Responsible for determine is process schedule allowed. If handled process definition declares "exclude_definitions"
 * option, then scheduling of processes defined in "exclude_definitions" won't be allowed until
 * the process will be handled.
 */
class ExcludeDefinitionsProcessSchedulePolicy implements ProcessSchedulePolicy
{
    /**
     * @var array
     */
    protected $excludeDefinitions = array();
    
    /**
     * Not allow scheduling definition if it's excluded by some process.
     *
     * @param ProcessTrigger $processTrigger
     * @param ProcessData $processData
     * @return bool
     */
    public function isScheduleAllowed(ProcessTrigger $processTrigger, ProcessData $processData)
    {
        $name = $processTrigger->getDefinition()->getName();

        return !isset($this->excludeDefinitions[$name]) || $this->excludeDefinitions[$name] == 0;
    }

    /**
     * Collect information about excluded definitions.
     *
     * @param ProcessHandleEvent $event
     */
    public function onProcessHandleBefore(ProcessHandleEvent $event)
    {
        $excludeDefinitions = $event->getProcessTrigger()->getDefinition()->getExcludeDefinitions();

        foreach ($excludeDefinitions as $name) {
            if (!isset($this->excludeDefinitions[$name])) {
                $this->excludeDefinitions[$name] = 0;
            }
            $this->excludeDefinitions[$name] += 1;
        }
    }

    /**
     * Cleanup information about excluded definitions.
     *
     * @param ProcessHandleEvent $event
     */
    public function onProcessHandleAfterFlush(ProcessHandleEvent $event)
    {
        $excludeDefinitions = $event->getProcessTrigger()->getDefinition()->getExcludeDefinitions();

        foreach ($excludeDefinitions as $name) {
            if (isset($this->excludeDefinitions[$name]) && $this->excludeDefinitions[$name] > 0) {
                // As this event comes after "before" event, counter will eventually be 0.
                $this->excludeDefinitions[$name] -= 1;
            }
        }
    }
}
