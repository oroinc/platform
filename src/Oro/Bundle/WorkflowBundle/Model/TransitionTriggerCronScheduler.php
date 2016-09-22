<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class TransitionTriggerCronScheduler implements LoggerAwareInterface
{
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


    public function setLogger(LoggerInterface $logger)
    {
        if ($this->deferredScheduler instanceof LoggerAwareInterface) {
            $this->deferredScheduler->setLogger($logger);
        }
    }
}
