<?php

namespace Oro\Bundle\BatchBundle\Job;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;

/**
 * Handle case when job executed inside another job and performs EntityManger::clear
 * Cascade persist is not possible for JobInstance because of duplicates in database
 */
class DoctrineJobRepository implements JobRepositoryInterface
{
    private ManagerRegistry $managerRegistry;

    private ?EntityManagerInterface $jobEntityManager = null;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->managerRegistry = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function createJobExecution(JobInstance $jobInstance): JobExecution
    {
        if (null !== $jobInstance->getId()) {
            $jobInstance = $this->getJobManager()->merge($jobInstance);
        } else {
            $this->getJobManager()->persist($jobInstance);
        }

        $jobExecution = new JobExecution();
        $jobExecution->setJobInstance($jobInstance);

        $this->updateJobExecution($jobExecution);

        return $jobExecution;
    }

    /**
     * {@inheritdoc}
     */
    public function updateJobExecution(JobExecution $jobExecution): JobExecution
    {
        $jobManager = $this->getJobManager();

        $jobInstance = $jobExecution->getJobInstance();
        if ($jobInstance->getId()
            && UnitOfWork::STATE_DETACHED === $jobManager->getUnitOfWork()->getEntityState($jobInstance)
        ) {
            $jobInstance = $jobManager->merge($jobInstance);
            $jobExecution->setJobInstance($jobInstance);
        }

        $jobManager->persist($jobExecution);
        $jobManager->flush($jobExecution);

        return $jobExecution;
    }

    /**
     * {@inheritdoc}
     */
    public function updateStepExecution(StepExecution $stepExecution): StepExecution
    {
        $jobManager = $this->getJobManager();
        if ($stepExecution->getId()
            && UnitOfWork::STATE_DETACHED === $jobManager->getUnitOfWork()->getEntityState($stepExecution)
        ) {
            $stepExecution = $jobManager->merge($stepExecution);
        }

        /**
         * @see \Oro\Bundle\BatchBundle\Step\AbstractStep::execute
         * because of StepExecution is not configured to cascade persist JobExecution
         * to avoid an error "A new entity was found through the relationship
         * 'Oro\Bundle\BatchBundle\Entity\StepExecution#jobExecution' that was not configured to cascade
         * persist operations".
         */
        $jobExecution = $stepExecution->getJobExecution();
        if ($jobExecution->getId()
            && UnitOfWork::STATE_DETACHED === $jobManager->getUnitOfWork()->getEntityState($jobExecution)
        ) {
            $this->updateJobExecution($jobExecution);
        }

        $jobManager->persist($stepExecution);
        $jobManager->flush($stepExecution);

        return $stepExecution;
    }

    public function getJobManager(): EntityManager
    {
        if (null !== $this->jobEntityManager && !$this->jobEntityManager->isOpen()) {
            $this->resetEntityManager();
            $this->jobEntityManager = null;
        }

        if (null === $this->jobEntityManager) {
            $this->jobEntityManager = $this->managerRegistry->getManagerForClass(JobExecution::class);
            $this->jobEntityManager->getConfiguration()->setSQLLogger(null);
        } else {
            /**
             * ensure that the transaction is fully rolled back
             * in case if a nested transaction is rolled back but the entity manager is not closed
             * this may happen if the EntityManager::rollback() method is called
             * without the call of EntityManager::close() method
             * @link http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference
             * /transactions-and-concurrency.html#exception-handling
             */
            $connection = $this->jobEntityManager->getConnection();
            if ($connection->getTransactionNestingLevel() > 0 && $connection->isRollbackOnly()) {
                while ($connection->getTransactionNestingLevel() > 0) {
                    $connection->rollBack();
                }
            }
        }

        return $this->jobEntityManager;
    }

    /**
     * Replaces the closed entity manager with new instance of entity manager.
     */
    private function resetEntityManager(): void
    {
        $managers = $this->managerRegistry->getManagers();
        foreach ($managers as $name => $manager) {
            if (!$manager->getMetadataFactory()->isTransient(JobExecution::class)) {
                $this->managerRegistry->resetManager($name);
                break;
            }
        }
    }
}
