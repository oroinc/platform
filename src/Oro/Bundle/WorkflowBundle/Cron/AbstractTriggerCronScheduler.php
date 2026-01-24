<?php

namespace Oro\Bundle\WorkflowBundle\Cron;

use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Provides common functionality for scheduling workflow trigger cron jobs.
 *
 * This base class manages the deferred scheduler and logger setup for workflow trigger scheduling.
 * Subclasses should implement specific scheduling logic for different types of workflow triggers
 * (e.g., time-based triggers, event-based triggers).
 */
abstract class AbstractTriggerCronScheduler implements LoggerAwareInterface
{
    /** @var DeferredScheduler */
    protected $deferredScheduler;

    public function __construct(DeferredScheduler $deferredScheduler)
    {
        $this->deferredScheduler = $deferredScheduler;
        $this->setLogger(new NullLogger());
    }

    #[\Override]
    public function setLogger(LoggerInterface $logger)
    {
        if ($this->deferredScheduler instanceof LoggerAwareInterface) {
            $this->deferredScheduler->setLogger($logger);
        }
    }

    /**
     * @return void
     */
    public function flush()
    {
        $this->deferredScheduler->flush();
    }
}
