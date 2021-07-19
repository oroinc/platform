<?php

namespace Oro\Bundle\BatchBundle\EventListener;

use Oro\Bundle\BatchBundle\Event\EventInterface;
use Oro\Bundle\BatchBundle\Event\JobExecutionEvent;
use Oro\Bundle\BatchBundle\Monolog\Handler\BatchLogHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Set the job execution log file into the job execution instance
 */
class SetJobExecutionLogFileSubscriber implements EventSubscriberInterface
{
    private BatchLogHandler $logger;

    public function __construct(BatchLogHandler $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            EventInterface::BEFORE_JOB_EXECUTION => 'setJobExecutionLogFile',
        ];
    }

    public function setJobExecutionLogFile(JobExecutionEvent $event): void
    {
        $jobExecution = $event->getJobExecution();
        $jobExecution->setLogFile($this->logger->getFilename());
    }
}
