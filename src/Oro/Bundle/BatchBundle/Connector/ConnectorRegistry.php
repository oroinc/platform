<?php

namespace Oro\Bundle\BatchBundle\Connector;

use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Job\Job;
use Oro\Bundle\BatchBundle\Job\JobFactory;
use Oro\Bundle\BatchBundle\Job\JobInterface;
use Oro\Bundle\BatchBundle\Step\StepFactory;

/**
 * Registry of batch jobs by job types and connectors.
 */
class ConnectorRegistry
{
    private JobFactory $jobFactory;

    private StepFactory $stepFactory;

    /**
     * @var array
     *  [
     *      'job_type' => [
     *          'connector_name' => [
     *              'job_alias' => Job $job,
     *              // ...
     *          ],
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    private array $jobs = [];

    public function __construct(JobFactory $jobFactory, StepFactory $stepFactory)
    {
        $this->jobFactory = $jobFactory;
        $this->stepFactory = $stepFactory;
    }

    /**
     * Get a registered job definition from a JobInstance
     *
     * @throws \LogicException
     */
    public function getJob(JobInstance $jobInstance): ?JobInterface
    {
        $connector = $this->getConnector($jobInstance->getConnector(), $jobInstance->getType());
        if ($connector) {
            $job = $this->getConnectorJob($connector, $jobInstance->getAlias());
            if ($job) {
                $job->setConfiguration($jobInstance->getRawConfiguration());
                $jobInstance->setJob($job);

                return $job;
            }
        }

        return null;
    }

    /**
     * Get the list of jobs
     *
     * @param string $type
     *
     * @return JobInterface[]
     */
    public function getJobs(string $type): array
    {
        return $this->jobs[$type];
    }

    /**
     * Add a step to an existing job (or create it)
     */
    public function addStepToJob(
        string $jobConnector,
        string $jobType,
        string $jobAlias,
        string $jobTitle,
        string $stepTitle,
        string $stepClass,
        array $services,
        array $parameters
    ): void {
        if (!isset($this->jobs[$jobType][$jobConnector][$jobAlias])) {
            $this->jobs[$jobType][$jobConnector][$jobAlias] = $this->jobFactory->createJob($jobTitle);
        }

        /** @var Job $job */
        $job = $this->jobs[$jobType][$jobConnector][$jobAlias];

        $step = $this->stepFactory->createStep($stepTitle, $stepClass, $services, $parameters);
        $job->addStep($step);
    }

    public function getConnector(string $connector, string $type): ?array
    {
        return $this->jobs[$type][$connector] ?? null;
    }

    private function getConnectorJob(array $connector, string $jobAlias): ?Job
    {
        return $connector[$jobAlias] ?? null;
    }

    /**
     * Get list of connectors
     *
     * @param string|null $jobType
     *
     * @return string[]
     */
    public function getConnectors(?string $jobType = null): array
    {
        if ($jobType !== null) {
            if (isset($this->jobs[$jobType])) {
                return array_keys($this->jobs[$jobType]);
            }

            return [];
        }

        return array_unique(array_keys($this->jobs));
    }
}
