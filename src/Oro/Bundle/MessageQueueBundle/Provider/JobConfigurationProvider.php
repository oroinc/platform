<?php
namespace Oro\Bundle\MessageQueueBundle\Provider;

use Oro\Component\MessageQueue\Provider\JobConfigurationProviderInterface;

class JobConfigurationProvider implements JobConfigurationProviderInterface
{
    /**
     * @var array
     */
    private $configuration;

    /**
     * {@inheritdoc}
     */
    public function getTimeBeforeStaleForJobName($jobName)
    {
        return $this->configuration[$jobName] ?? $this->configuration['default'] ?? null;
    }
    /**
     * @param array $jobConfiguration
     */
    public function setConfiguration(array $jobConfiguration)
    {
        $this->configuration = $jobConfiguration;
    }
}
