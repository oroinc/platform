<?php
namespace Oro\Component\MessageQueue\Provider;

interface JobConfigurationProviderInterface
{
    /**
     * @param string $jobName
     * @return mixed
     */
    public function getTimeBeforeStaleForJobName($jobName);
}
