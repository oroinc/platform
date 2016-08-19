<?php
namespace Oro\Component\MessageQueue\Job;

class DependentJobService
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var Job
     */
    private $rootJob;

    /**
     * @param JobStorage|null $jobStorage
     * @param Job|null $rootJob
     */
    public function __construct(JobStorage $jobStorage = null, Job $rootJob = null)
    {
        $this->jobStorage = $jobStorage;
        $this->rootJob = $rootJob;
    }

    /**
     * @param string $topic
     * @param string|array $message
     * @param int|null $priority
     */
    public function addDependentJob($topic, $message, $priority = null)
    {
        if (! $this->rootJob) {
            throw new \LogicException('Root job is not set');
        }

        $data = $this->rootJob->getData();

        $dependentJob = [
            'topic' => $topic,
            'message' => $message,
        ];

        if ($priority) {
            $dependentJob['priority'] = $priority;
        }

        $data['dependentJobs'][] = $dependentJob;

        $this->rootJob->setData($data);
    }

    /**
     * @param Job $rootJob
     * @param \Closure $callback
     */
    public function setDependentJob(Job $rootJob, \Closure $callback)
    {
        if ($this->rootJob) {
            throw new \LogicException('Is not allowed to call method if rootJob is set');
        }

        if (! $this->jobStorage) {
            throw new \LogicException('Job storage is not set');
        }

        if (! $rootJob->isRoot()) {
            throw new \InvalidArgumentException(sprintf(
                'Only root jobs allowed but got child. id:"%s"',
                $rootJob->getId()
            ));
        }

        $this->jobStorage->saveJob($rootJob, function (Job $rootJob) use ($callback) {
            $data = $rootJob->getData();
            $data['dependentJobs'] = [];

            $rootJob->setData($data);

            $callback(new static(null, $rootJob));
        });
    }
}
