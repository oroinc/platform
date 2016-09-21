<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ProcessTriggerCronScheduler implements LoggerAwareInterface
{
    /** @var string */
    private static $command = HandleProcessTriggerCommand::NAME;

    /**
     * @var DeferredScheduler
     */
    private $deferredScheduler;

    /**
     * @param DeferredScheduler $deferredScheduler
     */
    public function __construct(DeferredScheduler $deferredScheduler)
    {
        $this->setLogger(new NullLogger());
        $this->deferredScheduler = $deferredScheduler;
    }

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
        $args = [
            sprintf('--name=%s', $trigger->getDefinition()->getName()),
            sprintf('--id=%d', $trigger->getId())
        ];

        return $args;
    }

    /**
     * Applies schedule modifications to database
     */
    public function flush()
    {
        return $this->deferredScheduler->flush();
    }

    public function setLogger(LoggerInterface $logger)
    {
        if ($this->deferredScheduler instanceof LoggerAwareInterface) {
            $this->deferredScheduler->setLogger($logger);
        }
    }
}
