<?php
namespace Oro\Bundle\MessageQueueBundle\Provider;

use Oro\Component\MessageQueue\Provider\JobConfigurationProviderInterface;

class JobConfigurationProvider implements JobConfigurationProviderInterface
{
    const JOBS_ARRAY_KEY = 'jobs';
    const JOB_NAME_DEFAULT_KEY = 'default';
    const JOB_NAME_DELIMITERS = ['dot' => '.', 'colon' => ':'];

    /**
     * @var array
     */
    private $configuration = [];

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
     * @param array $jobConfiguration
     */
    public function setConfiguration(array $jobConfiguration)
    {
        $this->configuration = $jobConfiguration;
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

        $jobNameDelimiter = $this->getJobNameDelimiter($jobName);
        $jobNameParts = explode($jobNameDelimiter, $jobName);
        array_pop($jobNameParts);
        if (count($jobNameParts)) {
            return $this->checkKeyByJobNameOrItsPart(implode($jobNameDelimiter, $jobNameParts));
        }

        return null;
    }

    /**
     * @param $jobName
     *
     * @return mixed
     */
    private function getJobNameDelimiter($jobName)
    {
        foreach (self::JOB_NAME_DELIMITERS as $delimiter) {
            if (false !== strpos($jobName, $delimiter)) {
                return $delimiter;
            }
        }

        return self::JOB_NAME_DELIMITERS['dot'];
    }
}
