<?php

namespace Oro\Bundle\BatchBundle\Job;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository as BaseRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\UnitOfWork;

/**
 * Handle case when job executed inside another job and performs EntityManger::clear
 * Cascade persist is not possible for JobInstance because of duplicates in database
 */
class DoctrineJobRepository extends BaseRepository
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /**
     * @param ManagerRegistry $doctrine
     * @param string          $jobExecutionClass
     */
    public function __construct(ManagerRegistry $doctrine, $jobExecutionClass = JobExecution::class)
    {
        $this->doctrine = $doctrine;
        $this->jobExecutionClass = $jobExecutionClass;
    }

    /**
     * {@inheritdoc}
     */
    public function updateJobExecution(JobExecution $jobExecution)
    {
        $jobManager = $this->getJobManager();

        $jobInstance = $jobExecution->getJobInstance();
        if ($jobInstance->getId()
            && UnitOfWork::STATE_DETACHED === $jobManager->getUnitOfWork()->getEntityState($jobInstance)
        ) {
            $jobInstance = $jobManager->merge($jobInstance);
            $jobExecution->setJobInstance($jobInstance);
        }

        parent::updateJobExecution($jobExecution);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStepExecution(StepExecution $stepExecution)
    {
        $jobManager = $this->getJobManager();
        if ($stepExecution->getId()
            && UnitOfWork::STATE_DETACHED === $jobManager->getUnitOfWork()->getEntityState($stepExecution)
        ) {
            $stepExecution = $jobManager->merge($stepExecution);
        }

        /**
         * @see \Akeneo\Bundle\BatchBundle\Step\AbstractStep::execute
         * because of StepExecution is not configured to cascade persist JobExecution
         * to avoid an error "A new entity was found through the relationship
         * 'Akeneo\Bundle\BatchBundle\Entity\StepExecution#jobExecution' that was not configured to cascade
         * persist operations".
         */
        $jobExecution = $stepExecution->getJobExecution();
        if ($jobExecution->getId()
            && UnitOfWork::STATE_DETACHED === $jobManager->getUnitOfWork()->getEntityState($jobExecution)
        ) {
            $this->updateJobExecution($jobExecution);
        }

        parent::updateStepExecution($stepExecution);
    }

    /**
     * {@inheritdoc}
     */
    public function getJobManager()
    {
        if (null !== $this->jobManager && !$this->jobManager->isOpen()) {
            $this->resetEntityManager();
            $this->jobManager = null;
        }

        if (null === $this->jobManager) {
            $this->jobManager = $this->doctrine->getManagerForClass($this->jobExecutionClass);
            $this->jobManager->getConfiguration()->setSQLLogger(null);
        } else {
            /**
             * ensure that the transaction is fully rolled back
             * in case if a nested transaction is rolled back but the entity manager is not closed
             * this may happen if the EntityManager::rollback() method is called
             * without the call of EntityManager::close() method
             * @link http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/transactions-and-concurrency.html#exception-handling
             */
            $connection = $this->jobManager->getConnection();
            if ($connection->getTransactionNestingLevel() > 0 && $connection->isRollbackOnly()) {
                while ($connection->getTransactionNestingLevel() > 0) {
                    $connection->rollBack();
                }
            }
        }

        return parent::getJobManager();
    }

    /**
     * Replaces the closed entity manager with new instance of entity manager.
     */
    private function resetEntityManager()
    {
        $managers = $this->doctrine->getManagers();
        foreach ($managers as $name => $manager) {
            if (!$manager->getMetadataFactory()->isTransient($this->jobExecutionClass)) {
                $this->doctrine->resetManager($name);
                break;
            }
        }
    }
}
