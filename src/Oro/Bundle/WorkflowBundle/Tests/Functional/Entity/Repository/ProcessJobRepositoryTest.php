<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Util\ClassUtils;
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
 * @dbReindex
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

        $this->registry = $this->getContainer()->get('doctrine');

        $this->entityManager = $this->registry->getManagerForClass('OroWorkflowBundle:ProcessJob');
        $this->repository = $this->registry->getRepository('OroWorkflowBundle:ProcessJob');
    }

    public function testDeleteByHashes()
    {
        // prepare environment
        $this->loadFixtures(array('Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessEntities'));

        $definition = $this->entityManager->find(
            'OroWorkflowBundle:ProcessDefinition',
            LoadProcessEntities::FIRST_DEFINITION
        );
        $trigger = $this->entityManager->getRepository('OroWorkflowBundle:ProcessTrigger')
            ->findOneBy(array('definition' => $definition));

        // fixture data
        $entity = $this->getUser();
        $jobsAmount = ProcessJobRepository::DELETE_HASH_BATCH + 1;
        $entityHashes = array();

        for ($i = 0; $i < $jobsAmount; $i++) {
            $job = new ProcessJob();
            $job->setProcessTrigger($trigger)
                ->setEntityId($i)
                ->setData(new ProcessData(array('entity' => $entity)));
            $this->entityManager->persist($job);

            $entityHashes[] = $job->getEntityHash();
        }

        $this->entityManager->flush();

        // test
        $this->assertEquals($jobsAmount, $this->getJobsCount($entityHashes));
        $this->repository->deleteByHashes($entityHashes);
        $this->assertEquals(0, $this->getJobsCount($entityHashes));
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
}
