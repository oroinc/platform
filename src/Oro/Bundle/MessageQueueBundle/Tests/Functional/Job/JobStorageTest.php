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
    private function getEntityManager(): EntityManager
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(Job::class);
    }

    /**
     * @param string $name
     *
     * @return Job
     */
    protected function getJobReference($name): Job
    {
        return $this->getReference($name);
    }

    public function testShouldFindJobById(): void
    {
        $job = $this->getJobReference(LoadJobData::JOB_1);

        $this->getEntityManager()->clear();
        $foundJob = $this->jobStorage->findJobById($job->getId());
        $this->assertEquals($job->getId(), $foundJob->getId());
    }

    public function testShouldFindRootJobByJobNameAndStatuses(): void
    {
        $job = $this->getJobReference(LoadJobData::JOB_1);

        $this->getEntityManager()->clear();
        $foundJob = $this->jobStorage->findRootJobByJobNameAndStatuses(
            $job->getName(),
            [$job->getStatus()]
        );
        $this->assertEquals($job->getId(), $foundJob->getId());
    }

    public function testGetChildStatusesWithJobCountByRootJob(): void
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

    public function testGetChildJobIdsByRootJobAndStatus(): void
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
