<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
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

    public function testFindEntity()
    {
        $entityIdFirst  = 'first';
        $entityIdSecond = 'second';

        $user = $this->registry->getRepository('OroUserBundle:User')->find(1);

        $entityFirst = new WorkflowDefinition();
        $entityFirst
            ->setLabel('labelFirst')
            ->setName($entityIdFirst)
            ->setRelatedEntity($user)
            ->setEntityAttributeName('firstName');
        $this->entityManager->persist($entityFirst);

        $entitySecond = new WorkflowDefinition();
        $entitySecond
            ->setLabel('labelSecond')
            ->setName($entityIdSecond)
            ->setRelatedEntity($user)
            ->setEntityAttributeName('secondName');
        $this->entityManager->persist($entitySecond);

        $entityClass = ClassUtils::getClass($entityFirst);

        $processDefinition = new ProcessDefinition();
        $processDefinition
            ->setName('test')
            ->setLabel('Test')
            ->setRelatedEntity($entityClass);
        $this->entityManager->persist($processDefinition);

        $processTrigger = new ProcessTrigger();
        $processTrigger
            ->setDefinition($processDefinition)
            ->setEvent(ProcessTrigger::EVENT_UPDATE);
        $this->entityManager->persist($processTrigger);

        $processJob = new ProcessJob();
        $processJob
            ->setProcessTrigger($processTrigger)
            ->setEntityId($entityIdFirst);
        $this->entityManager->persist($processJob);

        $foundEntity = $this->repository->findEntity($processJob);
        $this->assertEquals($foundEntity, $entityFirst);
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
        $jobsAmount = ProcessJobRepository::DELETE_HASH_BATCH + 1;
        $entityHashes = array();

        for ($i = 0; $i < $jobsAmount; $i++) {
            $job = new ProcessJob();
            $job->setProcessTrigger($trigger)
                ->setEntityId($i);
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
}
