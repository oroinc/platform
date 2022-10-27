<?php

namespace Oro\Component\MessageQueue\Job\Extension;

use Oro\Component\MessageQueue\Job\Job;
use Oro\Component\MessageQueue\Job\RootJobStatusCalculatorInterface;

/**
 * This extension is used to recalculate root job status in cases:
 * job is started, job is finished or job is interrupted.
 */
class RootJobStatusExtension extends AbstractExtension
{
    /** @var RootJobStatusCalculatorInterface */
    private $rootJobStatusCalculator;

    public function __construct(RootJobStatusCalculatorInterface $rootJobStatusCalculator)
    {
        $this->rootJobStatusCalculator = $rootJobStatusCalculator;
    }

    /**
     * {@inheritdoc}
     */
    public function onPreRunUnique(Job $job)
    {
        $this->calculateJobStatusMessage($job);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostRunUnique(Job $job, $jobResult)
    {
        $this->calculateJobStatusMessage($job);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostRunDelayed(Job $job, $jobResult)
    {
        $this->calculateJobStatusMessage($job);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(Job $job)
    {
        $this->calculateJobStatusMessage($job);
    }

    private function calculateJobStatusMessage(Job $job): void
    {
        $this->rootJobStatusCalculator->calculate($job);
    }
}
