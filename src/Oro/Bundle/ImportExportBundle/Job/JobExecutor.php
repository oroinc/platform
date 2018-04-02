<?php

namespace Oro\Bundle\ImportExportBundle\Job;

use Akeneo\Bundle\BatchBundle\Connector\ConnectorRegistry;
use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Item\ExecutionContext;
use Akeneo\Bundle\BatchBundle\Job\BatchStatus;
use Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository as BatchJobRepository;
use Akeneo\Bundle\BatchBundle\Job\Job;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Exception\RuntimeException;
use Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorInterface;
use Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorRegistry;
use Oro\Bundle\ImportExportBundle\Job\Context\SimpleContextAggregator;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @todo: https://magecore.atlassian.net/browse/BAP-2600 move job results processing outside
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class JobExecutor
{
    const CONNECTOR_NAME = 'oro_importexport';

    /** @deprecated since 2.1, please use JOB_IMPORT_VALIDATION_FROM_CSV instead */
    const JOB_VALIDATE_IMPORT_FROM_CSV   = 'entity_import_validation_from_csv';
    const JOB_EXPORT_TO_CSV              = 'entity_export_to_csv';
    const JOB_EXPORT_TEMPLATE_TO_CSV     = 'entity_export_template_to_csv';
    const JOB_IMPORT_FROM_CSV            = 'entity_import_from_csv';
    const JOB_IMPORT_VALIDATION_FROM_CSV = 'entity_import_validation_from_csv';
    const JOB_CONTEXT_DATA_KEY           = 'contextData';
    const JOB_CONTEXT_AGGREGATOR_TYPE    = 'job_context_aggregator_type';

    /** @var EntityManager */
    protected $entityManager;

    /** @var ConnectorRegistry */
    protected $batchJobRegistry;

    /** @var ContextRegistry */
    protected $contextRegistry;

    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var BatchJobRepository */
    protected $batchJobRepository;

    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /** @var bool */
    protected $validationMode = false;

    /** var ContextAggregatorRegistry */
    protected $contextAggregatorRegistry;

    /**
     * @param ConnectorRegistry         $jobRegistry
     * @param BatchJobRepository        $batchJobRepository
     * @param ContextRegistry           $contextRegistry
     * @param ManagerRegistry           $managerRegistry
     * @param ContextAggregatorRegistry $contextAggregatorRegistry
     */
    public function __construct(
        ConnectorRegistry $jobRegistry,
        BatchJobRepository $batchJobRepository,
        ContextRegistry $contextRegistry,
        ManagerRegistry $managerRegistry,
        ContextAggregatorRegistry $contextAggregatorRegistry
    ) {
        $this->batchJobRegistry = $jobRegistry;
        $this->batchJobRepository = $batchJobRepository;
        $this->contextRegistry = $contextRegistry;
        $this->managerRegistry = $managerRegistry;
        $this->contextAggregatorRegistry = $contextAggregatorRegistry;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $jobType
     * @param string $jobName
     * @param array  $configuration
     *
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
     * @param JobInstance  $jobInstance
     * @param JobExecution $jobExecution
     *
     * @return JobResult
     */
    protected function doJob(JobInstance $jobInstance, JobExecution $jobExecution)
    {
        $jobResult = new JobResult();
        $jobResult->setSuccessful(false);

        $isTransactionStarted = false;
        if ($this->validationMode || !$this->isTransactionRunning()) {
            $this->entityManager->beginTransaction();
            $isTransactionStarted = true;
        }

        try {
            $job = $this->batchJobRegistry->getJob($jobInstance);
            if (!$job) {
                throw new RuntimeException(sprintf('Can\'t find job "%s"', $jobInstance->getAlias()));
            }

            $job->execute($jobExecution);
            $isSuccessful = $this->handleJobResult($jobExecution, $jobResult);

            if ($isTransactionStarted) {
                $isTransactionStarted = false;
                if ($isSuccessful && !$this->validationMode) {
                    $this->entityManager->commit();
                } else {
                    $this->entityManager->rollback();
                }
            }

            // trigger save of JobExecution and JobInstance
            $this->batchJobRepository->getJobManager()->flush();
            $this->batchJobRepository->getJobManager()->clear();
        } catch (\Exception $exception) {
            if ($isTransactionStarted) {
                $this->entityManager->rollback();
            }
            $jobExecution->addFailureException($exception);
            $jobResult->addFailureException($exception->getMessage());

            $this->saveFailedJobExecution($jobExecution);
        }

        $this->dispatchAfterJobExecutionEvent($jobExecution, $jobResult);

        return $jobResult;
    }

    /**
     * @param JobExecution $jobExecution
     * @param JobResult    $jobResult
     *
     * @return bool
     */
    protected function handleJobResult(JobExecution $jobExecution, JobResult $jobResult)
    {
        $failureExceptions = $this->collectFailureExceptions($jobExecution);

        foreach ($jobExecution->getAllFailureExceptions() as $failureException) {
            // in most cases this occurs in a race condition issue when couple of consumers try to process data
            // in which we have a UNIQUE constraint. workaround is to requeue a message with this job
            if ($failureException['class'] === UniqueConstraintViolationException::class) {
                $jobResult->setNeedRedelivery(true);
                return false;
            }
        }
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
        $this->batchJobRepository->updateJobExecution($jobExecution);
    }

    /**
     * @param string $jobCode
     *
     * @return array
     */
    public function getJobErrors($jobCode)
    {
        return $this->collectErrors($this->getJobExecutionByJobInstanceCode($jobCode));
    }

    /**
     * @param string $jobCode
     *
     * @return array
     */
    public function getJobFailureExceptions($jobCode)
    {
        return $this->collectFailureExceptions($this->getJobExecutionByJobInstanceCode($jobCode));
    }

    /**
     * @param string $jobCode
     *
     * @return JobExecution
     *
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
     *
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
     *
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
     *
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
     * @param JobResult   $jobResult
     * @param JobInstance $jobInstance
     */
    protected function setJobResultData(JobResult $jobResult, JobInstance $jobInstance)
    {
        $jobResult->setJobId($jobInstance->getId());
        $jobResult->setJobCode($jobInstance->getCode());

        /** @var JobExecution $jobExecution */
        $jobExecution = $jobInstance->getJobExecutions()->first();
        if ($jobExecution) {
            $contextAggregator = $this->getContextAggregator($jobExecution->getExecutionContext());
            $context = $contextAggregator->getAggregatedContext($jobExecution);
            if ($context) {
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
     * @param array  $configuration
     *
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
     * Create and persist job instance.
     *
     * @param string $jobType
     * @param string $jobName
     *
     * @return Job JobInstance
     */
    public function getJob($jobType, $jobName)
    {
        $jobInstance = new JobInstance(self::CONNECTOR_NAME, $jobType, $jobName);

        return $this->batchJobRegistry->getJob($jobInstance);
    }

    /**
     * Create JobExecution instance.
     *
     * @param array       $configuration
     * @param JobInstance $jobInstance
     *
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

    /**
     * @param JobExecution $jobExecution
     * @param JobResult    $jobResult
     */
    protected function dispatchAfterJobExecutionEvent(JobExecution $jobExecution, JobResult $jobResult)
    {
        if ($this->eventDispatcher && $this->eventDispatcher->hasListeners(Events::AFTER_JOB_EXECUTION)) {
            $this->eventDispatcher->dispatch(
                Events::AFTER_JOB_EXECUTION,
                new AfterJobExecutionEvent($jobExecution, $jobResult)
            );
        }
    }

    /**
     * @param ExecutionContext $executionContext
     *
     * @return ContextAggregatorInterface
     */
    protected function getContextAggregator(ExecutionContext $executionContext)
    {
        $aggregatorType = $executionContext->get(self::JOB_CONTEXT_AGGREGATOR_TYPE);
        if (!$aggregatorType) {
            $aggregatorType = SimpleContextAggregator::TYPE;
        }

        return $this->contextAggregatorRegistry->getAggregator($aggregatorType);
    }
}
