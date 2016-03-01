<?php

namespace Oro\Bundle\BatchBundle\Job;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\UnitOfWork;

use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository as BaseRepository;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;

/**
 * Handle case when job executed inside another job and performs EntityManger::clear
 * Cascade persist is not possible for JobInstance because of duplicates in database
 */
class DoctrineJobRepository extends BaseRepository
{
    /** {@inheritdoc} */
    public function __construct(EntityManager $entityManager, $jobExecutionClass)
    {
        parent::__construct($entityManager, $jobExecutionClass);

        $this->jobManager->getConfiguration()->setSQLLogger(null);
    }

    /**
     * {@inheritdoc}
     */
    public function updateJobExecution(JobExecution $jobExecution)
    {
        $uow = $this->getJobManager()->getUnitOfWork();
        $jobInstance = $jobExecution->getJobInstance();
        if ($jobInstance && UnitOfWork::STATE_DETACHED === $uow->getEntityState($jobInstance)) {
            /** @var JobInstance $jobInstance */
            $jobInstance = $uow->merge($jobInstance);
            $jobExecution->setJobInstance($jobInstance);
        }

        parent::updateJobExecution($jobExecution);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStepExecution(StepExecution $stepExecution)
    {
        $jobExecution = $stepExecution->getJobExecution();
        if ($jobExecution) {
            $this->updateJobExecution($jobExecution);
        }

        parent::updateStepExecution($stepExecution);
    }
}
