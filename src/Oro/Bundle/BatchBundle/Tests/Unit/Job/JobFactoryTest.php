<?php

namespace Oro\Bundle\BatchBundle\Tests\Unit\Job;

use Oro\Bundle\BatchBundle\Job\JobFactory;
use Oro\Bundle\BatchBundle\Job\JobRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class JobFactoryTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateJob(): void
    {
        $jobRepository = $this->createMock(JobRepositoryInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $jobFactory = new JobFactory($eventDispatcher, $jobRepository);
        $title = 'sample_job';
        $job = $jobFactory->createJob($title);

        self::assertEquals($title, $job->getName());
        self::assertSame($jobRepository, $job->getJobRepository());
    }
}
