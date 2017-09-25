<?php

namespace Oro\Bundle\MessageQueueBundle\Log;

use Oro\Component\MessageQueue\Job\AbstractExtension;
use Oro\Component\MessageQueue\Job\Job;

/**
 * Updates the consumer state with the current job.
 */
class JobExtension extends AbstractExtension
{
    /** @var ConsumerState */
    private $consumerState;

    /**
     * @param ConsumerState $consumerState
     */
    public function __construct(ConsumerState $consumerState)
    {
        $this->consumerState = $consumerState;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreRunUnique(Job $job)
    {
        $this->consumerState->setJob($job);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostRunUnique(Job $job, $jobResult)
    {
        $this->consumerState->setJob();
    }

    /**
     * {@inheritdoc}
     */
    public function onPreRunDelayed(Job $job)
    {
        $this->consumerState->setJob($job);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostRunDelayed(Job $job, $jobResult)
    {
        $this->consumerState->setJob();
    }
}
