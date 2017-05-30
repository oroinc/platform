<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Job;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures\LoadJobData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\MessageQueue\Job\DuplicateJobException;
use Oro\Component\MessageQueue\Job\JobStorage;

class JobStorageTest extends WebTestCase
{
    /** @var EntityManager */
    private $entityManager;

    /** @var JobStorage */
    private $jobStorage;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();

        $this->entityManager = $this->getContainer()->get('doctrine.orm.message_queue_job_entity_manager');
        $this->jobStorage = $this->getContainer()->get('oro_message_queue.job.storage');

        $this->loadFixtures([
            LoadJobData::class
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->entityManager = null;
        $this->jobStorage = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataFixtureExtecurotEntityManager()
    {
        return $this->entityManager;
    }

    public function testCouldGetJobStorageAsServiceFromContainer()
    {
        $this->assertInstanceOf(JobStorage::class, $this->jobStorage);
    }

    public function testShouldFindJobById()
    {
        /** @var Job $referenceJob */
        $referenceJob = $this->getReference(LoadJobData::JOB_1);
        $resultJob = $this->jobStorage->findJobById($referenceJob->getId());
        $this->assertSame($referenceJob, $resultJob);
    }

    public function testShouldFindRootJobByJobNameAndStatuses()
    {
        /** @var Job $referenceJob */
        $referenceJob = $this->getReference(LoadJobData::JOB_1);
        $resultJob = $this->jobStorage->findRootJobByJobNameAndStatuses(
            $referenceJob->getName(),
            [$referenceJob->getStatus()]
        );
        $this->assertSame($referenceJob, $resultJob);
    }

    public function testCouldCreateJobWithoutLock()
    {
        $rootJob = new Job();
        $rootJob->setOwnerId('owner-id');
        $rootJob->setName('name');
        $rootJob->setStatus(Job::STATUS_NEW);
        $rootJob->setCreatedAt(new \DateTime());

        $this->jobStorage->saveJob($rootJob);

        $job = new Job();
        $job->setName('name');
        $job->setStatus(Job::STATUS_NEW);
        $job->setCreatedAt(new \DateTime());
        $job->setRootJob($rootJob);

        $this->jobStorage->saveJob($job);

        $resultJob = $this->jobStorage->findJobById($job->getId());

        $this->assertNotEmpty($job->getId());
        $this->assertEquals($job->getId(), $resultJob->getId());
    }

    public function testCouldUpdateJobWithoutLock()
    {
        $job = $this->getReference(LoadJobData::JOB_3);

        $job->setStatus(Job::STATUS_FAILED);
        $this->jobStorage->saveJob($job);

        $resultJob = $this->jobStorage->findJobById($job->getId());

        $this->assertNotEmpty($job->getId());
        $this->assertEquals($job->getId(), $resultJob->getId());
        $this->assertEquals(Job::STATUS_FAILED, $resultJob->getStatus());
    }

    public function testCouldUpdateJobWithLock()
    {
        $job = $this->getReference(LoadJobData::JOB_4);

        $this->jobStorage->saveJob($job, function (Job $job) {
            $job->setStatus(Job::STATUS_CANCELLED);
        });

        $resultJob = $this->jobStorage->findJobById($job->getId());

        $this->assertNotEmpty($job->getId());
        $this->assertEquals($job->getId(), $resultJob->getId());
        $this->assertEquals(Job::STATUS_CANCELLED, $resultJob->getStatus());
    }

    public function testShouldThrowIfDuplicateJob()
    {
        /** @var Job $existedJob */
        $existedJob = $this->getReference(LoadJobData::JOB_1);

        $job = new Job();
        $job->setOwnerId($existedJob->getOwnerId());
        $job->setName($existedJob->getName());
        $job->setUnique(true);
        $job->setStatus(Job::STATUS_NEW);
        $job->setCreatedAt(new \DateTime());

        $this->expectException(DuplicateJobException::class);
        $this->jobStorage->saveJob($job);
    }
}
