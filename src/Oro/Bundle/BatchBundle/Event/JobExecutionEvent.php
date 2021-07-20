<?php

namespace Oro\Bundle\BatchBundle\Event;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event triggered during job execution
 */
class JobExecutionEvent extends Event implements EventInterface
{
    private JobExecution $jobExecution;

    public function __construct(JobExecution $jobExecution)
    {
        $this->jobExecution = $jobExecution;
    }

    public function getJobExecution(): JobExecution
    {
        return $this->jobExecution;
    }
}
