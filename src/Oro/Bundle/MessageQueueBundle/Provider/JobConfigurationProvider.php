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
        return $this->checkKeyByJobNameOrItsPart($jobName)
            ?? $this->configuration[self::JOB_NAME_DEFAULT_KEY]
            ?? null;
    }

    /**
     * @param $jobName
     *
     * @return null|int
     */
    private function checkKeyByJobNameOrItsPart($jobName)
    {
        if (isset($this->configuration[self::JOBS_ARRAY_KEY][$jobName])
            && $this->configuration[self::JOBS_ARRAY_KEY][$jobName]
        ) {
            return $this->configuration[self::JOBS_ARRAY_KEY][$jobName];
        }
        preg_match_all("/(\w+)\.*/", $jobName, $jobNameParts);
        array_pop($jobNameParts[1]);
        if (count($jobNameParts[1])) {
            return $this->checkKeyByJobNameOrItsPart(implode(".", $jobNameParts[1]));
        }
        return null;
    }
    /**
     * @param array $jobConfiguration
     */
    public function setConfiguration(array $jobConfiguration)
    {
        $this->configuration = $jobConfiguration;
    }
}
