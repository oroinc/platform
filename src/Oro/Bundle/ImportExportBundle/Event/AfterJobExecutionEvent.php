<?php

namespace Oro\Bundle\ImportExportBundle\Event;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Symfony\Component\EventDispatcher\Event;

class AfterJobExecutionEvent extends Event
{
    /** @var JobExecution */
    protected $jobExecution;

    /** @var JobResult */
    protected $jobResult;

    /**
     * @param JobExecution $jobExecution
     * @param JobResult $jobResult
     */
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
