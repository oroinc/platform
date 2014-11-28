<?php

namespace Oro\Bundle\ImportExportBundle\Job;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository;

class JobRepository extends DoctrineJobRepository
{
    /* @var EntityManager */
    protected $jobManager;

    /* @var EntityManager */
    protected $defaultManager;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->defaultManager = $entityManager;
        $this->initializeJobManager();
    }

    /**
     * Provides the doctrine entity manager
     *
     * @return EntityManager
     */
    protected function initializeJobManager()
    {
        if (null === $this->jobManager || (!$this->jobManager->isOpen())) {
            $currentConn = $this->defaultManager->getConnection();

            $currentConnParams = $currentConn->getParams();
            if (isset($currentConnParams['pdo'])) {
                unset($currentConnParams['pdo']);
            }

            $jobConn = new Connection(
                $currentConnParams,
                $currentConn->getDriver(),
                $currentConn->getConfiguration()
            );

            $jobManager = EntityManager::create(
                $jobConn,
                $this->defaultManager->getConfiguration()
            );

            $this->jobManager = $jobManager;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getJobManager()
    {
        $this->initializeJobManager();

        return parent::getJobManager();
    }

    /**
     * {@inheritdoc}
     */
    public function createJobExecution(JobInstance $jobInstance)
    {
        $this->initializeJobManager();

        return parent::createJobExecution($jobInstance);
    }

    /**
     * {@inheritdoc}
     */
    public function updateJobExecution(JobExecution $jobExecution)
    {
        $this->initializeJobManager();

        return parent::updateJobExecution($jobExecution);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStepExecution(StepExecution $stepExecution)
    {
        $this->initializeJobManager();

        return parent::updateStepExecution($stepExecution);
    }
}
