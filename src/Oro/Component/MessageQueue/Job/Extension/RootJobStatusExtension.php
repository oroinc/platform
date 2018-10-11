<?php

namespace Oro\Component\MessageQueue\Job\Extension;

use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\Topics;

/**
 * This extension is used to send root job recalculation message based on event instead of from the code
 */
class RootJobStatusExtension extends AbstractExtension
{
    /** @var MessageProducerInterface */
    private $producer;

    /**
     * @param MessageProducerInterface $producer
     */
    public function __construct(MessageProducerInterface $producer)
    {
        $this->producer = $producer;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreRunUnique(Job $job)
    {
        $this->sendCalculateJobStatusMessage($job);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostRunUnique(Job $job, $jobResult)
    {
        $this->sendCalculateJobStatusMessage($job);
    }

    /**
     * {@inheritdoc}
     */
    public function onPreRunDelayed(Job $job)
    {
        $this->sendCalculateJobStatusMessage($job);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostRunDelayed(Job $job, $jobResult)
    {
        $this->sendCalculateJobStatusMessage($job);
    }

    /**
     * {@inheritdoc}
     */
    public function onCancel(Job $job)
    {
        $this->sendCalculateJobStatusMessage($job);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(Job $job)
    {
        $this->sendCalculateJobStatusMessage($job);
    }

    /**
     * @param Job $job
     */
    private function sendCalculateJobStatusMessage($job)
    {
        $message = ['jobId' => $job->getId(), 'calculateProgress' => true];
        $this->producer->send(Topics::CALCULATE_ROOT_JOB_STATUS, new Message($message, MessagePriority::HIGH));
    }
}
