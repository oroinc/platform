<?php

namespace Oro\Bundle\IntegrationBundle\Event;

use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Symfony\Component\EventDispatcher\Event;

/**
 * This event could be used in order to customize configuration of sync jobs in third party bundles.
 * Another use case is to customize job result in order to prepare exception, hide sensitive data etc.
 */
class SyncEvent extends Event
{
    const SYNC_BEFORE = 'oro_integration.event.sync_before';
    const SYNC_AFTER  = 'oro_integration.event.sync_after';

    /** @var string */
    protected $jobName;

    /** @var array */
    protected $configuration;

    /** @var JobResult */
    protected $jobResult;

    /**
     * @param string    $jobName
     * @param array     $configuration
     * @param JobResult $jobResult
     */
    public function __construct($jobName, array $configuration, JobResult $jobResult = null)
    {
        $this->jobName       = $jobName;
        $this->configuration = $configuration;
        $this->jobResult     = $jobResult;
    }

    /**
     * @return string
     */
    public function getJobName()
    {
        return $this->jobName;
    }

    /**
     * @return array
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * @param array $configuration
     */
    public function setConfiguration(array $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return JobResult
     */
    public function getJobResult()
    {
        return $this->jobResult;
    }
}
