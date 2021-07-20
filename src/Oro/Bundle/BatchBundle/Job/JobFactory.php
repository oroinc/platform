<?php

namespace Oro\Bundle\BatchBundle\Job;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Instantiates a Job
 */
class JobFactory
{
    private JobRepositoryInterface $jobRepository;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher, JobRepositoryInterface $jobRepository)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->jobRepository = $jobRepository;
    }

    public function createJob(string $name): Job
    {
        $job = new Job($name);
        $job->setJobRepository($this->jobRepository);
        $job->setEventDispatcher($this->eventDispatcher);

        return $job;
    }
}
