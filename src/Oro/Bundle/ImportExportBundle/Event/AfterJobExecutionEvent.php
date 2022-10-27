<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Represents the event after a job execution is finished.
 */
class AfterJobExecutionEvent extends Event
{
    /** @var JobExecution */
    protected $jobExecution;

    /** @var JobResult */
    protected $jobResult;

    public function __construct(JobExecution $jobExecution, JobResult $jobResult)
    {
        $this->jobExecution = $jobExecution;
        $this->jobResult = $jobResult;
    }

    /**
     * @return JobExecution
     */
    public function getJobExecution()
    {
        return $this->jobExecution;
    }

    /**
     * @return JobResult
     */
    public function getJobResult()
    {
        return $this->jobResult;
    }
}
