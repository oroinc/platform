<?php
namespace Oro\Bundle\MessageQueueBundle\Provider;

use Oro\Component\MessageQueue\Provider\JobConfigurationProviderInterface;

class JobConfigurationProvider implements JobConfigurationProviderInterface
{
    const TIME_BEFORE_STALE_KEY = 'time_before_stale';
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
        return $this->configuration[self::TIME_BEFORE_STALE_KEY][$jobName] ??
            $this->configuration[self::TIME_BEFORE_STALE_KEY][self::JOB_NAME_DEFAULT_KEY] ??
            null;
    }
    /**
     * @param array $jobConfiguration
     */
    public function setConfiguration(array $jobConfiguration)
    {
        $this->configuration = $jobConfiguration;
    }
}
