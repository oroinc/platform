<?php

namespace Oro\Component\MessageQueue\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Job\JobManager;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;

/** Extension is used when there is need to stop consumer when all unique jobs already processed */
class UniqueJobsProcessedExtension extends AbstractExtension
{
    private JobManager $jobManager;

    public function __construct(JobManager $jobManager)
    {
        $this->jobManager = $jobManager;
    }

    public function onIdle(Context $context)
    {
        if (empty($this->jobManager->getUniqueJobs())) {
            $context->getLogger()->debug('Consumer has been stopped because all unique jobs have been processed');

            $context->setExecutionInterrupted(true);
            $context->setInterruptedReason('Unique jobs are processed.');
        }
    }
}
