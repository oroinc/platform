<?php
namespace Oro\Bundle\MessageQueueBundle\Provider;

use Oro\Component\MessageQueue\Provider\JobConfigurationProviderInterface;

class JobConfigurationProvider implements JobConfigurationProviderInterface
{
    const JOBS_ARRAY_KEY = 'jobs';
    const JOB_NAME_DEFAULT_KEY = 'default';
    /**
     * @var array
     */
    private $configuration;

    /**
     * {@inheritdoc}
     */
    public function getTimeBeforeStaleForJobName($jobName)
    {
        return $this->configuration[self::JOBS_ARRAY_KEY][$jobName]
            ?? $this->configuration[self::JOB_NAME_DEFAULT_KEY]
            ?? null;
    }
    /**
     * @param array $jobConfiguration
     */
    public function setConfiguration(array $jobConfiguration)
    {
        $this->configuration = $jobConfiguration;
    }
}
