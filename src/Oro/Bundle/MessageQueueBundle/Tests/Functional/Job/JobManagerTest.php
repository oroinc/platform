<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Job;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\MessageQueueBundle\Job\JobManager;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures\LoadJobData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Job\DuplicateJobException;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JobManagerTest extends WebTestCase
{
    /** @var JobManager */
    private $jobManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadJobData::class]);
        $this->jobManager = self::getContainer()->get('oro_message_queue.job.manager');
    }

    public function testCancelChildJobsWithStatusNew(): void
    {
        $startedAt = new \DateTime('-1 minute');
        $stoppedAt = new \DateTime();

        /** @var Job $childJob */
        $childJob1 = $this->getReference(LoadJobData::JOB_6);
        $childJob2 = $this->getReference(LoadJobData::JOB_9);
        $this->assertEquals(Job::STATUS_NEW, $childJob1->getStatus());
        $this->assertNull($childJob1->getStartedAt());
        $this->assertNull($childJob1->getStoppedAt());

        $this->assertEquals(Job::STATUS_NEW, $childJob2->getStatus());
        $this->assertNull($childJob2->getStartedAt());
        $this->assertNull($childJob2->getStoppedAt());

        $rootJob = $this->getReference(LoadJobData::JOB_5);
        $this->jobManager->setCancelledStatusForChildJobs($rootJob, [Job::STATUS_NEW], $stoppedAt, $startedAt);

        $this->getJobEntityManager()->clear(Job::class);
        $childJob1 = $this->getJobEntityManager()->find(Job::class, $childJob1->getId());
        $this->assertEquals(Job::STATUS_CANCELLED, $childJob1->getStatus());
        $this->assertEquals($startedAt->getTimestamp(), $childJob1->getStartedAt()->getTimestamp());
        $this->assertEquals($stoppedAt->getTimestamp(), $childJob1->getStoppedAt()->getTimestamp());

        $childJob2 = $this->getJobEntityManager()->find(Job::class, $childJob2->getId());
        $this->assertEquals(Job::STATUS_CANCELLED, $childJob2->getStatus());
        $this->assertEquals($startedAt->getTimestamp(), $childJob2->getStartedAt()->getTimestamp());
        $this->assertEquals($stoppedAt->getTimestamp(), $childJob2->getStoppedAt()->getTimestamp());
    }

    public function testCancelChildJobsWithStatusRunning(): void
    {
        $stoppedAt = new \DateTime();

        /** @var Job $childJob */
        $childJob = $this->getReference(LoadJobData::JOB_7);
        $this->assertEquals(Job::STATUS_RUNNING, $childJob->getStatus());
        $this->assertNull($childJob->getStoppedAt());

        $rootJob = $this->getReference(LoadJobData::JOB_5);
        $this->jobManager->setCancelledStatusForChildJobs($rootJob, [Job::STATUS_RUNNING], $stoppedAt);

        $this->getJobEntityManager()->clear(Job::class);
        $childJob = $this->getJobEntityManager()->find(Job::class, $childJob->getId());
        $this->assertEquals(Job::STATUS_CANCELLED, $childJob->getStatus());
        $this->assertNull($childJob->getStartedAt());
        $this->assertEquals($stoppedAt->getTimestamp(), $childJob->getStoppedAt()->getTimestamp());
    }

    public function testSaveJobOnlySpecifiedJob(): void
    {
        $job = $this->getReference(LoadJobData::JOB_3);
        $job->setStatus(Job::STATUS_FAILED);

        $anotherJob = $this->getReference(LoadJobData::JOB_1);
        $anotherJob->setStatus(Job::STATUS_SUCCESS);

        $this->jobManager->saveJob($job);

        $updatedJob = $this->getJobRepository()->findJobById($job->getId());
        $anotherJobAfterFlush = $this->getJobRepository()->findJobById($anotherJob->getId());

        $this->assertEquals(Job::STATUS_FAILED, $updatedJob->getStatus());
        $this->assertEquals(Job::STATUS_NEW, $anotherJobAfterFlush->getStatus());
    }

    public function testSaveJobWithLockOnlySpecifiedJob(): void
    {
        $job = $this->getReference(LoadJobData::JOB_3);

        $anotherJob = $this->getReference(LoadJobData::JOB_1);

        $this->jobManager->saveJobWithLock(
            $job,
            static function (Job $job) use ($anotherJob) {
                $job->setStatus(Job::STATUS_CANCELLED);
                $anotherJob->setStatus(Job::STATUS_SUCCESS);
            }
        );

        $updatedJob = $this->getJobRepository()->findJobById($job->getId());
        $anotherJobAfterFlush = $this->getJobRepository()->findJobById($anotherJob->getId());

        $this->assertEquals(Job::STATUS_CANCELLED, $updatedJob->getStatus());
        $this->assertEquals(Job::STATUS_NEW, $anotherJobAfterFlush->getStatus());
    }

    public function testSaveJobDuplicateJobException(): void
    {
        $existingJob = $this->getReference(LoadJobData::JOB_1);

        $job = new Job();
        $job->setOwnerId($existingJob->getOwnerId());
        $job->setName($existingJob->getName());
        $job->setUnique(true);
        $job->setStatus(Job::STATUS_NEW);
        $job->setCreatedAt(new \DateTime());

        $this->expectException(DuplicateJobException::class);
        $this->jobManager->saveJob($job);
    }

    public function testSaveJobNew(): void
    {
        $job = new Job();
        $job->setOwnerId('2-owner-id');
        $job->setName('2-name');
        $job->setStatus(Job::STATUS_NEW);
        $job->setCreatedAt(new \DateTime());
        $jobProperties = ['key' => 'value'];
        $job->setProperties($jobProperties);

        $this->jobManager->saveJob($job);

        // guard - job should be saved
        $this->assertNotEmpty($job->getId());

        $createdJob = $this->getJobRepository()->findJobById($job->getId());

        $this->assertEquals($job->getId(), $createdJob->getId());
        $this->assertEquals($jobProperties, $createdJob->getProperties());
    }

    public function testSaveJobUpdate(): void
    {
        $job = $this->getReference(LoadJobData::JOB_3);
        $job->setStatus(Job::STATUS_FAILED);
        $jobProperties = ['key' => 'value'];
        $job->setProperties($jobProperties);

        $this->jobManager->saveJob($job);

        $updatedJob = $this->getJobRepository()->findJobById($job->getId());

        $this->assertEquals(Job::STATUS_FAILED, $updatedJob->getStatus());
        $this->assertEquals($jobProperties, $updatedJob->getProperties());
    }

    public function testSaveJobWithLock(): void
    {
        $job = $this->getReference(LoadJobData::JOB_3);

        $this->jobManager->saveJobWithLock(
            $job,
            static function (Job $job) {
                $job->setStatus(Job::STATUS_CANCELLED);
            }
        );

        $updatedJob = $this->getJobRepository()->findJobById($job->getId());

        $this->assertEquals(Job::STATUS_CANCELLED, $updatedJob->getStatus());
    }

    public function testSaveJobUpdateChildJob(): void
    {
        $rootJob = $this->getReference(LoadJobData::JOB_1);
        $rootJob->setStatus(Job::STATUS_CANCELLED);

        $childJob = $this->getReference(LoadJobData::JOB_2);
        $childJob->setStatus(Job::STATUS_CANCELLED);

        $rootJob->addChildJob($childJob);

        $this->jobManager->saveJob($childJob);

        $updatedRootJob = $this->getJobRepository()->findJobById($rootJob->getId());
        $updatedChildJob = $this->getJobRepository()->findJobById($childJob->getId());

        $this->assertEquals(Job::STATUS_CANCELLED, $updatedChildJob->getStatus(), 'unexpected status of child job');
        $this->assertEquals(Job::STATUS_NEW, $updatedRootJob->getStatus(), 'unexpected status of root job');
    }

    public function testSaveJobWithLockUpdateChildJob(): void
    {
        $rootJob = $this->getReference(LoadJobData::JOB_1);

        $childJob = $this->getReference(LoadJobData::JOB_2);

        $rootJob->addChildJob($childJob);

        $this->jobManager->saveJobWithLock(
            $childJob,
            static function (Job $job) {
                $job->setStatus(Job::STATUS_CANCELLED);
                $job->getRootJob()->setStatus(Job::STATUS_CANCELLED);
            }
        );

        $updatedRootJob = $this->getJobRepository()->findJobById($rootJob->getId());
        $updatedChildJob = $this->getJobRepository()->findJobById($childJob->getId());

        $this->assertEquals(Job::STATUS_CANCELLED, $updatedChildJob->getStatus(), 'unexpected status of child job');
        $this->assertEquals(Job::STATUS_NEW, $updatedRootJob->getStatus(), 'unexpected status of root job');
    }

    public function testSaveJobCreateJobWhenTransactionIsRolledBackButEntityManagerIsNotClosed(): void
    {
        $job = new Job();
        $job->setOwnerId('3-owner-id');
        $job->setName('3-name');
        $job->setStatus(Job::STATUS_NEW);
        $job->setCreatedAt(new \DateTime());

        // emulate invalid transaction handling
        $this->getJobEntityManager()->rollback();

        $this->jobManager->saveJob($job);

        // guard - job should be saved
        $this->assertNotEmpty($job->getId());

        $this->getJobEntityManager()->clear();
        $createdJob = $this->getJobRepository()->findJobById($job->getId());

        $this->assertEquals($job->getId(), $createdJob->getId());

        // stop and delete the created job manually because it was created outside of the transaction
        $this->jobManager->saveJobWithLock(
            $createdJob,
            static function (Job $job) {
                $job->setStoppedAt(new \DateTime());
            }
        );
        $this->getJobEntityManager()->remove($createdJob);
        $this->getJobEntityManager()->flush($createdJob);
    }

    public function testSaveJobUpdateJobWhenTransactionIsRolledBackButEntityManagerIsNotClosed(): void
    {
        $job = $this->getReference(LoadJobData::JOB_3);
        $job->setStatus(Job::STATUS_CANCELLED);

        // emulate invalid transaction handling
        $this->getJobEntityManager()->rollback();

        $this->jobManager->saveJob($job);

        // the job should not be found because we updated a record that does not exist in DB
        $this->assertNull($this->getJobRepository()->findJobById($job->getId()));
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataFixturesExecutorEntityManager(): EntityManagerInterface
    {
        return $this->getJobEntityManager();
    }

    private function getJobEntityManager(): EntityManagerInterface
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(Job::class);
    }

    private function getJobRepository(): JobRepository
    {
        return $this->getJobEntityManager()->getRepository(Job::class);
    }
}
