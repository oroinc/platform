<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\StatusCalculator;

use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Job\JobManager;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures\LoadJobData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\StatusCalculator\CollectionCalculator;

/**
 * @dbIsolationPerTest
 */
class CollectionCalculatorTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadJobData::class]);
    }

    protected function getDataFixturesExecutorEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(Job::class);
    }

    public function testCalculateRootJobProgressForAsyncChild(): void
    {
        /** @var Job $job */
        $job = $this->getJobReference(LoadJobData::JOB_5);
        $this->assertInstanceOf(PersistentCollection::class, $job->getChildJobs());
        $this->assertFalse($job->getChildJobs()->isInitialized());

        /** @var CollectionCalculator $calculator */
        $calculator = $this->getContainer()->get('oro_message_queue.status_calculator.collection_calculator');
        $calculator->init($job);

        $job->getChildJobs()->initialize();
        $this->assertTrue($job->getChildJobs()->isInitialized());
        $this->assertCount(4, $job->getChildJobs());

        $this->updateChildJobs($job);

        $this->assertTrue($job->getChildJobs()->isInitialized());
        $this->assertCount(4, $job->getChildJobs());

        $this->assertEquals('0.8571', $calculator->calculateRootJobProgress());

        $this->assertTrue($job->getChildJobs()->isInitialized());
        $this->assertCount(7, $job->getChildJobs());
    }

    public function testCalculateRootJobStatusForAsyncChild(): void
    {
        /** @var Job $job */
        $job = $this->getJobReference(LoadJobData::JOB_5);
        $this->assertInstanceOf(PersistentCollection::class, $job->getChildJobs());
        $this->assertFalse($job->getChildJobs()->isInitialized());

        /** @var CollectionCalculator $calculator */
        $calculator = $this->getContainer()->get('oro_message_queue.status_calculator.collection_calculator');
        $calculator->init($job);

        $job->getChildJobs()->initialize();
        $this->assertTrue($job->getChildJobs()->isInitialized());
        $this->assertCount(4, $job->getChildJobs());

        $this->updateChildJobs($job);

        $this->assertTrue($job->getChildJobs()->isInitialized());
        $this->assertCount(4, $job->getChildJobs());

        $this->assertEquals(Job::STATUS_RUNNING, $calculator->calculateRootJobStatus());

        $this->assertTrue($job->getChildJobs()->isInitialized());
        $this->assertCount(7, $job->getChildJobs());
    }

    private function updateChildJobs(Job $job): void
    {
        /** @var JobManager $jobManager */
        $jobManager = $this->getContainer()->get('oro_message_queue.job.manager');

        foreach ($job->getChildJobs() as $childJob) {
            $childJob->setStatus(Job::STATUS_SUCCESS);
            $jobManager->saveJob($childJob);
        }

        $child = new Job();
        $child->setName($job->getName().'.child_new');
        $child->setStatus(Job::STATUS_NEW);
        $child->setRootJob($job);
        $child->setCreatedAt(new \DateTime());
        $jobManager->saveJob($child);

        $child = new Job();
        $child->setName($job->getName().'.child_failed');
        $child->setStatus(Job::STATUS_FAILED);
        $child->setRootJob($job);
        $child->setCreatedAt(new \DateTime());
        $jobManager->saveJob($child);

        $child = new Job();
        $child->setName($job->getName().'.child_complete');
        $child->setStatus(Job::STATUS_SUCCESS);
        $child->setRootJob($job);
        $child->setCreatedAt(new \DateTime());
        $jobManager->saveJob($child);
    }

    private function getJobReference(string $name): ?Job
    {
        return $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepository(Job::class)
            ->findOneBy(['name' => $name]);
    }
}
