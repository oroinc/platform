<?php
namespace Oro\Component\MessageQueue\Provider;

interface JobConfigurationProviderInterface
{
    /**
     * @param string $jobName
     * @return integer|null number of seconds
     *
     * Method should return number of seconds, after which job should be considered as stale.
     * If it returns null or -1 job will never be staled.
     */
    public function getTimeBeforeStaleForJobName($jobName);
}
