<?php

namespace Oro\Bundle\WorkflowBundle\Cron;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

/**
 * Add and remove processes to scheduler
 */
class ProcessTriggerCronScheduler extends AbstractTriggerCronScheduler
{
    /**
     * @throws \InvalidArgumentException
     */
    public function add(ProcessTrigger $trigger)
    {
        if (!$trigger->getCron()) {
            throw new \InvalidArgumentException(
                sprintf('%s supports only cron schedule triggers.', __CLASS__)
            );
        }

        $this->deferredScheduler->addSchedule(
            'oro:process:handle-trigger',
            $this->buildArguments($trigger),
            $trigger->getCron()
        );
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function removeSchedule(ProcessTrigger $trigger)
    {
        if (!$trigger->getCron()) {
            throw new \InvalidArgumentException(
                sprintf('%s supports only cron schedule triggers.', __CLASS__)
            );
        }

        $this->deferredScheduler->removeSchedule(
            'oro:process:handle-trigger',
            $this->buildArguments($trigger),
            $trigger->getCron()
        );
    }

    /**
     * @param ProcessTrigger $trigger
     *
     * @return array
     */
    protected function buildArguments(ProcessTrigger $trigger)
    {
        return [
            sprintf('--name=%s', $trigger->getDefinition()->getName()),
            sprintf('--id=%d', $trigger->getId())
        ];
    }
}
