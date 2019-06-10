<?php

namespace Oro\Bundle\WorkflowBundle\Cron;

use Oro\Bundle\WorkflowBundle\Command\HandleTransitionCronTriggerCommand;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;

/**
 * Schedules cron commands to execute transition.
 */
class TransitionTriggerCronScheduler extends AbstractTriggerCronScheduler
{
    /**
     * @param TransitionCronTrigger $cronTrigger
     * @return void
     */
    public function addSchedule(TransitionCronTrigger $cronTrigger)
    {
        //using late arguments resolving, as arguments depends on Trigger ID and not persisted has no ID yet
        $argumentsCallback = function () use ($cronTrigger) {
            return $this->buildArguments($cronTrigger);
        };

        $this->deferredScheduler->addSchedule(
            HandleTransitionCronTriggerCommand::getDefaultName(),
            $argumentsCallback,
            $cronTrigger->getCron()
        );
    }

    /**
     * @param TransitionCronTrigger $cronTrigger
     * @return void
     */
    public function removeSchedule(TransitionCronTrigger $cronTrigger)
    {
        $this->deferredScheduler->removeSchedule(
            HandleTransitionCronTriggerCommand::getDefaultName(),
            $this->buildArguments($cronTrigger),
            $cronTrigger->getCron()
        );
    }

    /**
     * @param TransitionCronTrigger $cronTrigger
     * @return array
     */
    protected function buildArguments(TransitionCronTrigger $cronTrigger)
    {
        return [
            sprintf('--id=%d', $cronTrigger->getId())
        ];
    }
}
