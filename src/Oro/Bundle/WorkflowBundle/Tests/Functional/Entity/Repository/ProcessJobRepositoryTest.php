<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\WorkflowBundle\Entity\Repository\ProcessJobRepository;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Bundle\WorkflowBundle\Tests\Functional\DataFixtures\LoadProcessEntities;

class ProcessJobRepositoryTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProcessEntities::class]);
    }

    public function testDeleteByHashes()
    {
        // fixture data
        $jobsAmount = ProcessJobRepository::DELETE_HASH_BATCH + 1;
        $entityHashes = $this->createProcessJobs($jobsAmount);

        // test
        $this->assertEquals($jobsAmount, $this->getJobsCount($entityHashes));
        $this->getRepository()->deleteByHashes($entityHashes);
        $this->assertEquals(0, $this->getJobsCount($entityHashes));
    }

    public function testFindByIds()
    {
        $count = 5;
        $this->createProcessJobs($count);

        $expectedJobs = $this->getRepository()->findAll();

        $this->assertCount($count, $expectedJobs);

        $ids = [];
        /** @var ProcessJob $job */
        foreach ($expectedJobs as $job) {
            $ids[] = $job->getId();
        }

        $this->assertCount($count, $ids);

        $actualJobs = $this->getRepository()->findByIds($ids);

        $this->assertEquals($expectedJobs, $actualJobs);

        array_shift($ids);

        $actualJobs = $this->getRepository()->findByIds($ids);

        $this->assertCount($count - 1, $actualJobs);
    }

    private function getDoctrine(): ManagerRegistry
    {
        return $this->getContainer()->get('doctrine');
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return $this->getDoctrine()->getManagerForClass(ProcessJob::class);
    }

    private function getRepository(): ProcessJobRepository
    {
        return $this->getDoctrine()->getRepository(ProcessJob::class);
    }

    private function getJobsCount(array $hashes): int
    {
        $queryBuilder = $this->getRepository()->createQueryBuilder('job')
            ->select('COUNT(job.id) as jobsCount');

        return (int)$queryBuilder->where($queryBuilder->expr()->in('job.entityHash', $hashes))
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function getUser(): User
    {
        return $this->getDoctrine()->getRepository(User::class)
            ->createQueryBuilder('user')
            ->setMaxResults(1)
            ->getQuery()
            ->getSingleResult();
    }

    private function createProcessJobs(int $count): array
    {
        $entityManager = $this->getEntityManager();
        $definition = $entityManager->find(
            ProcessDefinition::class,
            LoadProcessEntities::FIRST_DEFINITION
        );

        $trigger = $entityManager->getRepository(ProcessTrigger::class)
            ->findOneBy(['definition' => $definition]);

        $entity = $this->getUser();
        $entityHashes = [];

        for ($i = 0; $i < $count; $i++) {
            $processData = new ProcessData();
            $processData->set('data', $entity);

            $job = new ProcessJob();
            $job->setProcessTrigger($trigger)
                ->setEntityId($i)
                ->setData($processData);
            $entityManager->persist($job);

            $entityHashes[] = $job->getEntityHash();
        }

        $entityManager->flush();

        return $entityHashes;
    }
}
