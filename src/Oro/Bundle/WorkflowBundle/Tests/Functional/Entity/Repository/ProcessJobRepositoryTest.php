<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessEntities;

/**
 * @dbIsolation
 */
class ProcessJobRepositoryTest extends WebTestCase
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ProcessJobRepository
     */
    protected $repository;

    /**
     * @var Registry
     */
    protected $registry;

    protected function setUp()
    {
        $this->initClient();

        $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->beginTransaction();

        $this->dropJobsRecords();

        $this->registry      = $this->getContainer()->get('doctrine');
        $this->entityManager = $this->registry->getManagerForClass('OroWorkflowBundle:ProcessJob');
        $this->repository    = $this->registry->getRepository('OroWorkflowBundle:ProcessJob');

        $this->loadFixtures(['Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessEntities']);
    }

    protected function tearDown()
    {
        // clear DB from separate connection, close to avoid connection limit and memory leak
        $manager = $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager();
        $manager->rollback();
        $manager->getConnection()->close();

        $this->dropJobsRecords();

        parent::tearDown();
    }

    protected function dropJobsRecords()
    {
        $this->getContainer()
            ->get('doctrine')
            ->getManager()
            ->createQuery('DELETE OroWorkflowBundle:ProcessJob')
            ->execute();
    }

    public function testDeleteByHashes()
    {
        // fixture data
        $jobsAmount   = ProcessJobRepository::DELETE_HASH_BATCH + 1;
        $entityHashes = $this->createProcessJobs($jobsAmount);

        // test
        $this->assertEquals($jobsAmount, $this->getJobsCount($entityHashes));
        $this->repository->deleteByHashes($entityHashes);
        $this->assertEquals(0, $this->getJobsCount($entityHashes));
    }

    public function testFindByIds()
    {
        $count = 5;
        $this->createProcessJobs($count);

        $expectedJobs = $this->repository->findAll();

        $this->assertCount($count, $expectedJobs);

        $ids = [];
        /** @var ProcessJob $job */
        foreach ($expectedJobs as $job) {
            $ids[] = $job->getId();
        }

        $this->assertCount($count, $ids);

        $actualJobs = $this->repository->findByIds($ids);

        $this->assertEquals($expectedJobs, $actualJobs);

        array_shift($ids);

        $actualJobs = $this->repository->findByIds($ids);

        $this->assertCount($count - 1, $actualJobs);
    }

    /**
     * @param array $hashes
     * @return int
     */
    protected function getJobsCount(array $hashes)
    {
        $queryBuilder = $this->repository->createQueryBuilder('job')
            ->select('COUNT(job.id) as jobsCount');

        return (int)$queryBuilder->where($queryBuilder->expr()->in('job.entityHash', $hashes))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return User
     */
    protected function getUser()
    {
        return $this->registry->getRepository('OroUserBundle:User')
            ->createQueryBuilder('user')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    /**
     * @param integer $count
     * @return array
     */
    protected function createProcessJobs($count)
    {
        $definition = $this->entityManager->find(
            'OroWorkflowBundle:ProcessDefinition',
            LoadProcessEntities::FIRST_DEFINITION
        );

        $trigger = $this->entityManager->getRepository('OroWorkflowBundle:ProcessTrigger')
            ->findOneBy(['definition' => $definition]);

        $entity       = $this->getUser();
        $entityHashes = [];

        for ($i = 0; $i < $count; $i++) {
            $processData = new ProcessData();
            $processData->set('data', $entity);

            $job = new ProcessJob();
            $job->setProcessTrigger($trigger)
                ->setEntityId($i)
                ->setData($processData);
            $this->entityManager->persist($job);

            $entityHashes[] = $job->getEntityHash();
        }

        $this->entityManager->flush();

        return $entityHashes;
    }
}
