<?php

namespace Oro\Bundle\ImportExportBundle\Job;

use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Akeneo\Bundle\BatchBundle\Connector\ConnectorRegistry;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Job\BatchStatus;
use Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository as BatchJobRepository;

use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;

/**
 * @todo: https://magecore.atlassian.net/browse/BAP-2600 move job results processing outside
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class JobExecutor
{
    const CONNECTOR_NAME = 'oro_importexport';

    const JOB_EXPORT_TO_CSV = 'entity_export_to_csv';
    const JOB_EXPORT_TEMPLATE_TO_CSV = 'entity_export_template_to_csv';
    const JOB_IMPORT_FROM_CSV = 'entity_import_from_csv';
    const JOB_VALIDATE_IMPORT_FROM_CSV = 'entity_import_validation_from_csv';
    const JOB_CONTEXT_DATA_KEY = 'contextData';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ConnectorRegistry
     */
    protected $batchJobRegistry;

    /**
     * @var ContextRegistry
     */
    protected $contextRegistry;

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var BatchJobRepository
     */
    protected $batchJobRepository;

    /**
     * @var bool
     */
    protected $validationMode = false;

    /**
     * @param ConnectorRegistry $jobRegistry
     * @param BatchJobRepository $batchJobRepository
     * @param ContextRegistry $contextRegistry
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        ConnectorRegistry $jobRegistry,
        BatchJobRepository $batchJobRepository,
        ContextRegistry $contextRegistry,
        ManagerRegistry $managerRegistry
    ) {
        $this->batchJobRegistry = $jobRegistry;
        $this->batchJobRepository = $batchJobRepository;
        $this->contextRegistry = $contextRegistry;
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param string $jobType
     * @param string $jobName
     * @param array $configuration
     * @return JobResult
     */
    public function executeJob($jobType, $jobName, array $configuration = [])
    {
        $this->initialize();
        $jobInstance = $this->createJobInstance($jobType, $jobName, $configuration);
        $jobExecution = $this->createJobExecution($configuration, $jobInstance);

        $jobResult = $this->doJob($jobInstance, $jobExecution);
        $this->setJobResultData($jobResult, $jobInstance);

        return $jobResult;
    }

    /**
     * @param JobExecution $jobExecution
     * @param JobInstance $jobInstance
     * @return JobResult
     */
    protected function doJob(JobInstance $jobInstance, JobExecution $jobExecution)
    {
        $jobResult = new JobResult();
        $jobResult->setSuccessful(false);

        $isTransactionRunning = $this->isTransactionRunning();
        if (!$isTransactionRunning) {
            $this->entityManager->beginTransaction();
        }

        try {
            $job = $this->batchJobRegistry->getJob($jobInstance);
            if (!$job) {
                throw new RuntimeException(sprintf('Can\'t find job "%s"', $jobInstance->getAlias()));
            }

            $job->execute($jobExecution);
            $isSuccessful = $this->handleJobResult($jobExecution, $jobResult);

            if (!$isTransactionRunning && $isSuccessful && !$this->validationMode) {
                $this->entityManager->commit();
            } elseif (!$isTransactionRunning) {
                $this->entityManager->rollback();
            }

            // trigger save of JobExecution and JobInstance
            $this->batchJobRepository->getJobManager()->flush();
            $this->batchJobRepository->getJobManager()->clear();
        } catch (\Exception $exception) {
            if (!$isTransactionRunning) {
                $this->entityManager->rollback();
            }
            $jobExecution->addFailureException($exception);
            $jobResult->addFailureException($exception->getMessage());

            $this->saveFailedJobExecution($jobExecution);
        }

        return $jobResult;
    }

    /**
     * @param JobExecution $jobExecution
     * @param JobResult $jobResult
     *
     * @return bool
     */
    protected function handleJobResult(JobExecution $jobExecution, JobResult $jobResult)
    {
        $failureExceptions = $this->collectFailureExceptions($jobExecution);

        $isSuccessful = $jobExecution->getStatus()->getValue() === BatchStatus::COMPLETED && !$failureExceptions;
        if ($isSuccessful) {
            $jobResult->setSuccessful(true);
        } elseif ($failureExceptions) {
            foreach ($failureExceptions as $failureException) {
                $jobResult->addFailureException($failureException);
            }
        }

        return $isSuccessful;
    }

    /**
     * @return bool
     */
    protected function isTransactionRunning()
    {
        return $this->entityManager->getConnection()->getTransactionNestingLevel() !== 0;
    }

    /**
     * Try to save batch entities only in case when it's possible
     *
     * @param JobExecution $jobExecution
     */
    protected function saveFailedJobExecution(JobExecution $jobExecution)
    {
        $batchManager = $this->batchJobRepository->getJobManager();
        $batchUow     = $batchManager->getUnitOfWork();
        $couldBeSaved = $batchManager->isOpen()
            && $batchUow->getEntityState($jobExecution) === UnitOfWork::STATE_MANAGED;

        if ($couldBeSaved) {
            $batchManager->flush();
        }
    }

    /**
     * @param string $jobCode
     * @return array
     */
    public function getJobErrors($jobCode)
    {
        return $this->collectErrors($this->getJobExecutionByJobInstanceCode($jobCode));
    }

    /**
     * @param string $jobCode
     * @return array
     */
    public function getJobFailureExceptions($jobCode)
    {
        return $this->collectFailureExceptions($this->getJobExecutionByJobInstanceCode($jobCode));
    }

    /**
     * @param string $jobCode
     * @return JobExecution
     * @throws LogicException
     */
    protected function getJobExecutionByJobInstanceCode($jobCode)
    {
        /** @var JobInstance $jobInstance */
        $jobInstance = $this->getJobInstanceRepository()->findOneBy(['code' => $jobCode]);
        if (!$jobInstance) {
            throw new LogicException(sprintf('No job instance found with code %s', $jobCode));
        }

        /** @var JobExecution $jobExecution */
        $jobExecution = $jobInstance->getJobExecutions()->first();
        if (!$jobExecution) {
            throw new LogicException(sprintf('No job execution found for job instance with code %s', $jobCode));
        }

        return $jobExecution;
    }

    /**
     * @return EntityRepository
     */
    protected function getJobInstanceRepository()
    {
        return $this->managerRegistry->getRepository('AkeneoBatchBundle:JobInstance');
    }

    /**
     * @param JobExecution $jobExecution
     * @return array
     */
    protected function collectFailureExceptions(JobExecution $jobExecution)
    {
        $failureExceptions = [];
        foreach ($jobExecution->getAllFailureExceptions() as $exceptionData) {
            if (!empty($exceptionData['message'])) {
                $failureExceptions[] = $exceptionData['message'];
            }
        }

        return $failureExceptions;
    }

    /**
     * @param JobExecution $jobExecution
     * @return array
     */
    protected function collectErrors(JobExecution $jobExecution)
    {
        $errors = [];
        foreach ($jobExecution->getStepExecutions() as $stepExecution) {
            $errors = array_merge(
                $errors,
                $this->contextRegistry->getByStepExecution($stepExecution)->getErrors()
            );
        }

        return $errors;
    }

    /**
     * @param string $prefix
     * @return string
     */
    protected function generateJobCode($prefix = '')
    {
        if ($prefix) {
            $prefix .= '_';
        }

        $prefix .= date('Y_m_d_H_i_s') . '_';

        return preg_replace('~\W~', '_', uniqid($prefix, true));
    }

    /**
     * Initialize environment
     */
    protected function initialize()
    {
        $this->entityManager = $this->managerRegistry->getManager();
    }

    /**
     * Set data to JobResult
     * TODO: Find a way to work with multiple amount of job and step executions
     * TODO https://magecore.atlassian.net/browse/BAP-2600
     *
     * @param JobResult $jobResult
     * @param JobInstance $jobInstance
     */
    protected function setJobResultData(JobResult $jobResult, JobInstance $jobInstance)
    {
        $jobResult->setJobId($jobInstance->getId());
        $jobResult->setJobCode($jobInstance->getCode());

        /** @var JobExecution $jobExecution */
        $jobExecution = $jobInstance->getJobExecutions()->first();
        if ($jobExecution) {
            $stepExecutions = $jobExecution->getStepExecutions();
            /** @var StepExecution $firstStepExecution */
            $firstStepExecution = $stepExecutions->first();

            if ($firstStepExecution) {
                $context = $this->contextRegistry->getByStepExecution($firstStepExecution);

                if ($stepExecutions->count() > 1) {
                    /** @var StepExecution $stepExecution */
                    foreach ($stepExecutions->slice(1) as $stepExecution) {
                        ContextHelper::mergeContextCounters(
                            $context,
                            $this->contextRegistry->getByStepExecution($stepExecution)
                        );
                    }
                }

                $jobResult->setContext($context);
            }
        }

        $this->contextRegistry->clear($jobInstance);
    }

    /**
     * Create and persist job instance.
     *
     * @param string $jobType
     * @param string $jobName
     * @param array $configuration
     * @return JobInstance
     */
    protected function createJobInstance($jobType, $jobName, array $configuration)
    {
        $jobInstance = new JobInstance(self::CONNECTOR_NAME, $jobType, $jobName);
        $jobInstance->setCode($this->generateJobCode($jobName));
        $jobInstance->setLabel(sprintf('%s.%s', $jobType, $jobName));
        if (array_key_exists(self::JOB_CONTEXT_DATA_KEY, $configuration)) {
            unset($configuration[self::JOB_CONTEXT_DATA_KEY]);
        }
        $jobInstance->setRawConfiguration($configuration);
        $this->batchJobRepository->getJobManager()->persist($jobInstance);

        return $jobInstance;
    }

    /**
     * Create JobExecution instance.
     *
     * @param array $configuration
     * @param JobInstance $jobInstance
     * @return JobExecution
     */
    protected function createJobExecution(array $configuration, JobInstance $jobInstance)
    {
        $jobExecution = $this->batchJobRepository->createJobExecution($jobInstance);

        // load configuration to context
        if ($configuration) {
            foreach ($configuration as $typeConfiguration) {
                if (!is_array($typeConfiguration)) {
                    continue;
                }

                foreach ($typeConfiguration as $name => $option) {
                    $jobExecution->getExecutionContext()->put($name, $option);
                }
            }
        }

        return $jobExecution;
    }

    /**
     * @param boolean $validationMode
     */
    public function setValidationMode($validationMode)
    {
        $this->validationMode = $validationMode;
    }

    /**
     * @return boolean
     */
    public function isValidationMode()
    {
        return $this->validationMode;
    }
}
