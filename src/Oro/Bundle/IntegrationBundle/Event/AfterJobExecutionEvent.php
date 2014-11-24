<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\ImportExportBundle\Job\JobResult;

class AfterJobExecutionEvent extends Event
{
    const NAME = 'oro_integration.after_job_execution.event';

    /** @var JobResult */
    protected $jobResult;

    /**
     * @param JobResult $jobResult
     */
    public function __construct(JobResult $jobResult)
    {
        $this->jobResult = $jobResult;
    }

    /**
     * @return JobResult
     */
    public function getJobResult()
    {
        return $this->jobResult;
    }
}
