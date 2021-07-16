<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\MessageQueueBundle\Entity\Job;
use Oro\Bundle\MessageQueueBundle\Entity\Repository\JobRepository;
use Oro\Bundle\MessageQueueBundle\Tests\Functional\DataFixtures\LoadJobData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class JobRepositoryTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadJobData::class]);
    }

    public function testGetChildJobErrorLogFiles()
    {
        $job = $this->getReference(LoadJobData::JOB_5);
        $expectedChildJob = $this->getReference(LoadJobData::JOB_8);

        $filesData = $this->getJobRepository()->getChildJobErrorLogFiles($job);
        $this->assertEquals([['id' => $expectedChildJob->getId(), 'error_log_file' => 'error_file.json']], $filesData);
    }

    public function testFindJobById(): void
    {
        $job = $this->getReference(LoadJobData::JOB_1);

        $this->getJobEntityManager()->clear();
        $foundJob = $this->getJobRepository()->findJobById($job->getId());
        $this->assertEquals($job->getId(), $foundJob->getId());
    }

    public function testFindRootJobByJobNameAndStatuses(): void
    {
        $job = $this->getReference(LoadJobData::JOB_1);

        $this->getJobEntityManager()->clear();
        $foundJob = $this->getJobRepository()->findRootJobByJobNameAndStatuses(
            $job->getName(),
            [$job->getStatus()]
        );
        $this->assertEquals($job->getId(), $foundJob->getId());
    }

    public function testGetChildStatusesWithJobCountByRootJob(): void
    {
        $job = $this->getReference(LoadJobData::JOB_5);

        $expectedResult = [
            Job::STATUS_NEW => 2,
            Job::STATUS_RUNNING => 1,
            Job::STATUS_CANCELLED => 1
        ];

        $result = $this->getJobRepository()->getChildStatusesWithJobCountByRootJob($job);

        $this->assertEquals($expectedResult, $result);
    }

    public function testGetChildJobIdsByRootJobAndStatus(): void
    {
        /** @var Job $job */
        $job = $this->getReference(LoadJobData::JOB_5);

        $expectedResult = [
            $this->getReference(LoadJobData::JOB_6)->getId(),
            $this->getReference(LoadJobData::JOB_9)->getId()
        ];

        /**
         * Result work of this function depends on engine
         * In PGSQL it returns array of ids in desc order, every id has integer type,
         * But in mysql it will be array of ids in asc order, every id has string type
         */
        $result = $this->getJobRepository()->getChildJobIdsByRootJobAndStatus($job, Job::STATUS_NEW);

        /**
         * Make result independent on db engine
         */
        sort($result);
        $preparedResult = array_map('intval', $result);

        $this->assertSame($expectedResult, $preparedResult);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDataFixturesExecutorEntityManager()
    {
        return $this->getJobEntityManager();
    }

    private function getJobEntityManager(): EntityManager
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass(Job::class);
    }

    /**
     * @return JobRepository|EntityRepository
     */
    private function getJobRepository(): JobRepository
    {
        return $this->getJobEntityManager()->getRepository(Job::class);
    }
}
