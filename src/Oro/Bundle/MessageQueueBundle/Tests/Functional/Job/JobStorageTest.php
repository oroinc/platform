<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Job;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures\LoadJobData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\MessageQueue\Job\JobStorage;

/**
 * @dbIsolationPerTest
 */
class JobStorageTest extends WebTestCase
{
    /** @var JobStorage */
    private $jobStorage;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->jobStorage = $this->getContainer()->get('oro_message_queue.job.storage');

        $this->loadFixtures([LoadJobData::class]);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->jobStorage = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataFixturesExecutorEntityManager()
    {
        return $this->getEntityManager();
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(Job::class);
    }

    /**
     * @param string $name
     *
     * @return Job
     */
    protected function getJobReference($name)
    {
        return $this->getReference($name);
    }

    /**
     * @return array
     */
    public function entityManagerStateDataProvider()
    {
        return [
            'not closed entity manager' => [false],
            'closed entity manager'     => [true],
        ];
    }

    public function testShouldFindJobById()
    {
        $job = $this->getJobReference(LoadJobData::JOB_1);

        $this->getEntityManager()->clear();
        $foundJob = $this->jobStorage->findJobById($job->getId());
        $this->assertEquals($job->getId(), $foundJob->getId());
    }

    public function testShouldFindRootJobByJobNameAndStatuses()
    {
        $job = $this->getJobReference(LoadJobData::JOB_1);

        $this->getEntityManager()->clear();
        $foundJob = $this->jobStorage->findRootJobByJobNameAndStatuses(
            $job->getName(),
            [$job->getStatus()]
        );
        $this->assertEquals($job->getId(), $foundJob->getId());
    }

    /**
     * @dataProvider entityManagerStateDataProvider
     */
    public function testShouldUpdateOnlySpecifiedJob($closed)
    {
        $job = $this->getJobReference(LoadJobData::JOB_3);
        $job->setStatus(Job::STATUS_FAILED);

        $anotherJob = $this->getJobReference(LoadJobData::JOB_1);
        $anotherJob->setStatus(Job::STATUS_SUCCESS);

        if ($closed) {
            $this->getEntityManager()->close();
        }
        $this->jobStorage->saveJob($job);

        $this->getEntityManager()->clear();
        $updatedJob = $this->jobStorage->findJobById($job->getId());
        $anotherJobAfterFlush = $this->jobStorage->findJobById($anotherJob->getId());

        $this->assertEquals(Job::STATUS_FAILED, $updatedJob->getStatus());
        $this->assertEquals(Job::STATUS_NEW, $anotherJobAfterFlush->getStatus());
    }

    /**
     * @dataProvider entityManagerStateDataProvider
     */
    public function testShouldUpdateOnlySpecifiedJobEntityWithLockCallback($closed)
    {
        $job = $this->getJobReference(LoadJobData::JOB_3);

        $anotherJob = $this->getJobReference(LoadJobData::JOB_1);

        if ($closed) {
            $this->getEntityManager()->close();
        }
        $this->jobStorage->saveJob($job, function (Job $job) use ($anotherJob) {
            $job->setStatus(Job::STATUS_CANCELLED);
            $anotherJob->setStatus(Job::STATUS_SUCCESS);
        });

        $this->getEntityManager()->clear();
        $updatedJob = $this->jobStorage->findJobById($job->getId());
        $anotherJobAfterFlush = $this->jobStorage->findJobById($anotherJob->getId());

        $this->assertEquals(Job::STATUS_CANCELLED, $updatedJob->getStatus());
        $this->assertEquals(Job::STATUS_NEW, $anotherJobAfterFlush->getStatus());
    }

    /**
     * @dataProvider entityManagerStateDataProvider
     * @expectedException \Oro\Component\MessageQueue\Job\DuplicateJobException
     */
    public function testShouldThrowIfDuplicateJob($closed)
    {
        $existingJob = $this->getJobReference(LoadJobData::JOB_1);

        $job = new Job();
        $job->setOwnerId($existingJob->getOwnerId());
        $job->setName($existingJob->getName());
        $job->setUnique(true);
        $job->setStatus(Job::STATUS_NEW);
        $job->setCreatedAt(new \DateTime());

        if ($closed) {
            $this->getEntityManager()->close();
        }
        $this->jobStorage->saveJob($job);
    }

    /**
     * @dataProvider entityManagerStateDataProvider
     */
    public function testShouldCreateJob($closed)
    {
        $job = new Job();
        $job->setOwnerId('owner-id');
        $job->setName('name');
        $job->setStatus(Job::STATUS_NEW);
        $job->setCreatedAt(new \DateTime());

        if ($closed) {
            $this->getEntityManager()->close();
        }
        $this->jobStorage->saveJob($job);

        // guard - job should be saved
        $this->assertNotEmpty($job->getId());

        $this->getEntityManager()->clear();
        $createdJob = $this->jobStorage->findJobById($job->getId());

        $this->assertEquals($job->getId(), $createdJob->getId());
    }

    /**
     * @dataProvider entityManagerStateDataProvider
     */
    public function testShouldCreateJobWithChildren($closed)
    {
        $rootJob = new Job();
        $rootJob->setOwnerId('owner-id');
        $rootJob->setName('name');
        $rootJob->setStatus(Job::STATUS_NEW);
        $rootJob->setCreatedAt(new \DateTime());

        $childJob = new Job();
        $childJob->setName('name');
        $childJob->setStatus(Job::STATUS_NEW);
        $childJob->setCreatedAt(new \DateTime());

        $childJob->setRootJob($rootJob);
        $rootJob->addChildJob($childJob);

        if ($closed) {
            $this->getEntityManager()->close();
        }
        $this->jobStorage->saveJob($rootJob);

        // guard - job should be saved
        $this->assertNotEmpty($rootJob->getId());
        $this->assertNotEmpty($childJob->getId());

        $this->getEntityManager()->clear();
        $createdRootJob = $this->jobStorage->findJobById($rootJob->getId());
        $createdChildJob = $this->jobStorage->findJobById($childJob->getId());

        $this->assertEquals($rootJob->getId(), $createdRootJob->getId(), 'unexpected id of root job');
        $this->assertEquals($childJob->getId(), $createdChildJob->getId(), 'unexpected id of child job');
    }

    /**
     * @dataProvider entityManagerStateDataProvider
     */
    public function testShouldUpdateJob($closed)
    {
        $job = $this->getJobReference(LoadJobData::JOB_3);
        $job->setStatus(Job::STATUS_FAILED);

        if ($closed) {
            $this->getEntityManager()->close();
        }
        $this->jobStorage->saveJob($job);

        $this->getEntityManager()->clear();
        $updatedJob = $this->jobStorage->findJobById($job->getId());

        $this->assertEquals(Job::STATUS_FAILED, $updatedJob->getStatus());
    }

    /**
     * @dataProvider entityManagerStateDataProvider
     */
    public function testShouldUpdateJobWithLockCallback($closed)
    {
        $job = $this->getJobReference(LoadJobData::JOB_3);

        if ($closed) {
            $this->getEntityManager()->close();
        }
        $this->jobStorage->saveJob($job, function (Job $job) {
            $job->setStatus(Job::STATUS_CANCELLED);
        });

        $this->getEntityManager()->clear();
        $updatedJob = $this->jobStorage->findJobById($job->getId());

        $this->assertEquals(Job::STATUS_CANCELLED, $updatedJob->getStatus());
    }

    /**
     * @dataProvider entityManagerStateDataProvider
     */
    public function testShouldUpdateJobWithChildren($closed)
    {
        $rootJob = $this->getJobReference(LoadJobData::JOB_1);
        $rootJob->setStatus(Job::STATUS_CANCELLED);

        $childJob = $this->getJobReference(LoadJobData::JOB_2);
        $childJob->setStatus(Job::STATUS_CANCELLED);

        $rootJob->addChildJob($childJob);

        if ($closed) {
            $this->getEntityManager()->close();
        }
        $this->jobStorage->saveJob($rootJob);

        $this->getEntityManager()->clear();
        $updatedRootJob = $this->jobStorage->findJobById($rootJob->getId());
        $updatedChildJob = $this->jobStorage->findJobById($childJob->getId());

        $this->assertEquals(Job::STATUS_CANCELLED, $updatedRootJob->getStatus(), 'unexpected status of root job');
        $this->assertEquals(Job::STATUS_NEW, $updatedChildJob->getStatus(), 'unexpected status of child job');
    }

    /**
     * @dataProvider entityManagerStateDataProvider
     */
    public function testShouldUpdateJobWithChildrenAndLockCallback($closed)
    {
        $rootJob = $this->getJobReference(LoadJobData::JOB_1);

        $childJob = $this->getJobReference(LoadJobData::JOB_2);

        $rootJob->addChildJob($childJob);

        if ($closed) {
            $this->getEntityManager()->close();
        }
        $this->jobStorage->saveJob($rootJob, function (Job $job) {
            $job->setStatus(Job::STATUS_CANCELLED);
            foreach ($job->getChildJobs() as $childJob) {
                $childJob->setStatus(Job::STATUS_CANCELLED);
            }
        });

        $this->getEntityManager()->clear();
        $updatedRootJob = $this->jobStorage->findJobById($rootJob->getId());
        $updatedChildJob = $this->jobStorage->findJobById($childJob->getId());

        $this->assertEquals(Job::STATUS_CANCELLED, $updatedRootJob->getStatus(), 'unexpected status of root job');
        $this->assertEquals(Job::STATUS_NEW, $updatedChildJob->getStatus(), 'unexpected status of child job');
    }

    /**
     * @dataProvider entityManagerStateDataProvider
     */
    public function testShouldUpdateChildJob($closed)
    {
        $rootJob = $this->getJobReference(LoadJobData::JOB_1);
        $rootJob->setStatus(Job::STATUS_CANCELLED);

        $childJob = $this->getJobReference(LoadJobData::JOB_2);
        $childJob->setStatus(Job::STATUS_CANCELLED);

        $rootJob->addChildJob($childJob);

        if ($closed) {
            $this->getEntityManager()->close();
        }
        $this->jobStorage->saveJob($childJob);

        $this->getEntityManager()->clear();
        $updatedRootJob = $this->jobStorage->findJobById($rootJob->getId());
        $updatedChildJob = $this->jobStorage->findJobById($childJob->getId());

        $this->assertEquals(Job::STATUS_CANCELLED, $updatedChildJob->getStatus(), 'unexpected status of child job');
        $this->assertEquals(Job::STATUS_NEW, $updatedRootJob->getStatus(), 'unexpected status of root job');
    }

    /**
     * @dataProvider entityManagerStateDataProvider
     */
    public function testShouldUpdateChildJobWithLockCallback($closed)
    {
        $rootJob = $this->getJobReference(LoadJobData::JOB_1);

        $childJob = $this->getJobReference(LoadJobData::JOB_2);

        $rootJob->addChildJob($childJob);

        if ($closed) {
            $this->getEntityManager()->close();
        }
        $this->jobStorage->saveJob($childJob, function (Job $job) {
            $job->setStatus(Job::STATUS_CANCELLED);
            $job->getRootJob()->setStatus(Job::STATUS_CANCELLED);
        });

        $this->getEntityManager()->clear();
        $updatedRootJob = $this->jobStorage->findJobById($rootJob->getId());
        $updatedChildJob = $this->jobStorage->findJobById($childJob->getId());

        $this->assertEquals(Job::STATUS_CANCELLED, $updatedChildJob->getStatus(), 'unexpected status of child job');
        $this->assertEquals(Job::STATUS_NEW, $updatedRootJob->getStatus(), 'unexpected status of root job');
    }

    public function testShouldCreateJobWhenTransactionIsRolledBackButEntityManagerIsNotClosed()
    {
        $job = new Job();
        $job->setOwnerId('owner-id');
        $job->setName('name');
        $job->setStatus(Job::STATUS_NEW);
        $job->setCreatedAt(new \DateTime());

        // emulate invalid transaction handling
        $this->getEntityManager()->rollback();

        $this->jobStorage->saveJob($job);

        // guard - job should be saved
        $this->assertNotEmpty($job->getId());

        $this->getEntityManager()->clear();
        $createdJob = $this->jobStorage->findJobById($job->getId());

        $this->assertEquals($job->getId(), $createdJob->getId());

        // stop and delete the created job manually because it was created outside of the transaction
        $this->jobStorage->saveJob($createdJob, function (Job $job) {
            $job->setStoppedAt(new \DateTime());
        });
        $this->getEntityManager()->remove($createdJob);
        $this->getEntityManager()->flush($createdJob);
    }

    public function testShouldUpdateJobWhenTransactionIsRolledBackButEntityManagerIsNotClosed()
    {
        $job = $this->getJobReference(LoadJobData::JOB_3);
        $job->setStatus(Job::STATUS_CANCELLED);

        // emulate invalid transaction handling
        $this->getEntityManager()->rollback();

        $this->jobStorage->saveJob($job);

        $this->getEntityManager()->clear();
        // the job should not be found because we updated a record that does not exist in DB
        $this->assertNull($this->jobStorage->findJobById($job->getId()));
    }

    public function testGetChildStatusesWithJobCountByRootJob()
    {
        /**
         * @var $job Job
         */
        $job = $this->getJobReference(LoadJobData::JOB_5);

        $expectedResult = [
            Job::STATUS_NEW => 2,
            Job::STATUS_RUNNING => 1,
            Job::STATUS_CANCELLED => 1
        ];

        $result = $this->jobStorage->getChildStatusesWithJobCountByRootJob($job);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetChildJobIdsByRootJobAndStatus()
    {
        /**
         * @var $job Job
         */
        $job = $this->getJobReference(LoadJobData::JOB_5);

        $expectedResult = [
            $this->getJobReference(LoadJobData::JOB_6)->getId(),
            $this->getJobReference(LoadJobData::JOB_9)->getId()
        ];

        /**
         * Result work of this function depends on engine
         * In PGSQL it returns array of ids in desc order, every id has integer type,
         * But in mysql it will be array of ids in asc order, every id has string type
         */
        $result = $this->jobStorage->getChildJobIdsByRootJobAndStatus($job, Job::STATUS_NEW);

        /**
         * Make result independent on db engine
         */
        sort($result);
        $preparedResult = array_map('intval', $result);

        $this->assertSame($expectedResult, $preparedResult);
    }
}
