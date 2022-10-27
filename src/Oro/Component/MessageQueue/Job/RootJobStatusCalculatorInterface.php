<?php

namespace Oro\Component\MessageQueue\Job;

/**
 * Calculate root job status and progress.
 */
interface RootJobStatusCalculatorInterface
{
    public function calculate(Job $job): void;
}
