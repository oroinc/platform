<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\WorkflowBundle\Command\HandleProcessCronTriggerCommand;
use Oro\Bundle\WorkflowBundle\Entity\TransitionCronTrigger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class TransitionTriggerCronScheduler implements LoggerAwareInterface
{
    /**
     * @var string
     */
    protected static $command = HandleProcessCronTriggerCommand::NAME;

    /**
     * @var DeferredScheduler
     */
    private $deferredScheduler;

    /**
     * @param DeferredScheduler $deferredScheduler
     */
    public function __construct(DeferredScheduler $deferredScheduler)
    {
        $this->deferredScheduler = $deferredScheduler;
        $this->setLogger(new NullLogger());
    }

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
            static::$command,
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
            static::$command,
            $this->buildArguments($cronTrigger),
            $cronTrigger->getCron()
        );
    }

    /**
     * @return void
     */
    public function flush()
    {
        $this->deferredScheduler->flush();
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

    public function setLogger(LoggerInterface $logger)
    {
        if ($this->deferredScheduler instanceof LoggerAwareInterface) {
            $this->deferredScheduler->setLogger($logger);
        }
    }
}
