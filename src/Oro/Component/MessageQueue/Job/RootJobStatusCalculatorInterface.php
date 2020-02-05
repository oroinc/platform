<?php

namespace Oro\Component\MessageQueue\Job;

/**
 * Calculate root job status and progress.
 */
interface RootJobStatusCalculatorInterface
{
    /**
     * @param Job $job
     *
     * @return void
     */
    public function calculate(Job $job): void;
}
