<?php

namespace Oro\Bundle\WorkflowBundle\Cron;

use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessTriggerCronScheduler extends AbstractTriggerCronScheduler
{
    /** @var string */
    private static $command = HandleProcessTriggerCommand::NAME;

    /**
     * @param ProcessTrigger $trigger
     *
     * @throws \InvalidArgumentException
     */
    public function add(ProcessTrigger $trigger)
    {
        if (!$trigger->getCron()) {
            throw new \InvalidArgumentException(
                sprintf('%s supports only cron schedule triggers.', __CLASS__)
            );
        }

        $this->deferredScheduler->addSchedule(self::$command, $this->buildArguments($trigger), $trigger->getCron());
    }

    /**
     * @param ProcessTrigger $trigger
     * @throws \InvalidArgumentException
     */
    public function removeSchedule(ProcessTrigger $trigger)
    {
        if (!$trigger->getCron()) {
            throw new \InvalidArgumentException(
                sprintf('%s supports only cron schedule triggers.', __CLASS__)
            );
        }

        $this->deferredScheduler->removeSchedule(self::$command, $this->buildArguments($trigger), $trigger->getCron());
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
