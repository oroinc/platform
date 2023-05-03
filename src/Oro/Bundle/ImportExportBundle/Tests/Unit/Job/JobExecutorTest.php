<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\Connector\ConnectorRegistry;
use Oro\Bundle\BatchBundle\Entity\JobExecution;
use Oro\Bundle\BatchBundle\Entity\JobInstance;
use Oro\Bundle\BatchBundle\Entity\StepExecution;
use Oro\Bundle\BatchBundle\Job\BatchStatus;
use Oro\Bundle\BatchBundle\Job\DoctrineJobRepository;
use Oro\Bundle\BatchBundle\Job\JobInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Exception\LogicException;
use Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorInterface;
use Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorRegistry;
use Oro\Bundle\ImportExportBundle\Job\Context\SimpleContextAggregator;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Job\JobResult;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class JobExecutorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $entityManager;

    /** @var Connection|\PHPUnit\Framework\MockObject\MockObject */
    private $connection;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var ConnectorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $batchJobRegistry;

    /** @var ContextRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextRegistry;

    /** @var DoctrineJobRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $batchJobRepository;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $batchJobManager;

    /** @var ContextAggregatorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAggregatorRegistry;

    /** @var JobExecutor */
    private $executor;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManager::class);
        $this->connection = $this->createMock(Connection::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->batchJobRegistry = $this->createMock(ConnectorRegistry::class);
        $this->contextRegistry = $this->createMock(ContextRegistry::class);
        $this->batchJobManager = $this->createMock(EntityManager::class);
        $this->batchJobRepository = $this->createMock(DoctrineJobRepository::class);
        $this->contextAggregatorRegistry = $this->createMock(ContextAggregatorRegistry::class);

        $this->entityManager->expects(self::any())
            ->method('getConnection')
            ->willReturn($this->connection);

        $this->doctrine->expects(self::any())
            ->method('getManager')
            ->willReturn($this->entityManager);

        $this->batchJobRepository->expects(self::any())
            ->method('getJobManager')
            ->willReturn($this->batchJobManager);

        $this->executor = new JobExecutor(
            $this->batchJobRegistry,
            $this->batchJobRepository,
            $this->contextRegistry,
            $this->doctrine,
            $this->contextAggregatorRegistry
        );
    }

    public function testExecuteJobUnknownJob(): void
    {
        $this->connection->expects(self::once())
            ->method('getTransactionNestingLevel')
            ->willReturn(0);
        $this->entityManager->expects(self::once())
            ->method('beginTransaction');
        $this->entityManager->expects(self::once())
            ->method('rollback');
        $this->entityManager->expects(self::never())
            ->method('commit');
        $this->batchJobRegistry->expects(self::once())
            ->method('getJob')
            ->with(self::isInstanceOf(JobInstance::class));
        $this->mockCreateJobExecutionWithStepExecution();
        $this->mockAggregatorContext(SimpleContextAggregator::TYPE);

        $result = $this->executor->executeJob('import', 'test');

        self::assertInstanceOf(JobResult::class, $result);
        self::assertFalse($result->isSuccessful());
        self::assertEquals(['Can\'t find job "test"'], $result->getFailureExceptions());
        self::assertStringStartsWith('test_' . date('Y_m_d_H_'), $result->getJobCode());
    }

    public function testExecuteJobSuccess(): void
    {
        $configuration = ['test' => true];

        $this->connection->expects(self::once())
            ->method('getTransactionNestingLevel')
            ->willReturn(0);
        $this->entityManager->expects(self::once())
            ->method('beginTransaction');
        $this->entityManager->expects(self::never())
            ->method('rollback');
        $this->entityManager->expects(self::once())
            ->method('commit');

        $this->batchJobManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(JobInstance::class));
        $this->batchJobManager->expects(self::once())
            ->method('flush');

        $context = $this->mockAggregatorContext(SimpleContextAggregator::TYPE);
        $stepExecution = $this->createMock(StepExecution::class);
        $stepExecution->expects(self::any())
            ->method('getFailureExceptions')
            ->willReturn([]);

        $job = $this->createMock(JobInterface::class);
        $job->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf(JobExecution::class))
            ->willReturnCallback(function (JobExecution $jobExecution) use ($configuration, $stepExecution) {
                self::assertEquals('import.test', $jobExecution->getJobInstance()->getLabel());
                self::assertEquals($configuration, $jobExecution->getJobInstance()->getRawConfiguration());

                $jobExecution->setStatus(new BatchStatus(BatchStatus::COMPLETED));
                $jobExecution->addStepExecution($stepExecution);
            });

        $this->batchJobRegistry->expects(self::once())
            ->method('getJob')
            ->with(self::isInstanceOf(JobInstance::class))
            ->willReturn($job);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(Events::AFTER_JOB_EXECUTION)
            ->willReturn(true);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(AfterJobExecutionEvent::class), Events::AFTER_JOB_EXECUTION);

        $this->executor->setEventDispatcher($dispatcher);

        $this->batchJobRepository->expects(self::any())
            ->method('createJobExecution')
            ->willReturnCallback(function ($instance) {
                $execution = new JobExecution();
                $execution->setJobInstance($instance);

                return $execution;
            });
        $result = $this->executor->executeJob('import', 'test', $configuration);

        self::assertInstanceOf(JobResult::class, $result);
        self::assertTrue($result->isSuccessful());
        self::assertEquals($context, $result->getContext());
    }

    public function testExecuteJobSuccessWithTransactionStarted(): void
    {
        $configuration = ['test' => true];

        $this->connection->expects(self::once())
            ->method('getTransactionNestingLevel')
            ->willReturn(1);

        $this->entityManager->expects(self::never())
            ->method('beginTransaction');
        $this->entityManager->expects(self::never())
            ->method('rollback');
        $this->entityManager->expects(self::never())
            ->method('commit');

        $this->batchJobManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(JobInstance::class));
        $this->batchJobManager->expects(self::once())
            ->method('flush');

        $stepExecution = $this->createMock(StepExecution::class);
        $stepExecution->expects(self::any())
            ->method('getFailureExceptions')
            ->willReturn([]);

        $job = $this->createMock(JobInterface::class);
        $job->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf(JobExecution::class))
            ->willReturnCallback(function (JobExecution $jobExecution) use ($configuration, $stepExecution) {
                self::assertEquals('import.test', $jobExecution->getJobInstance()->getLabel());
                self::assertEquals($configuration, $jobExecution->getJobInstance()->getRawConfiguration());
                $jobExecution->setStatus(new BatchStatus(BatchStatus::COMPLETED));
                $jobExecution->addStepExecution($stepExecution);
            });

        $this->batchJobRegistry->expects(self::once())
            ->method('getJob')
            ->with(self::isInstanceOf(JobInstance::class))
            ->willReturn($job);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(Events::AFTER_JOB_EXECUTION)
            ->willReturn(false);
        $dispatcher->expects(self::never())
            ->method('dispatch');

        $this->executor->setEventDispatcher($dispatcher);
        $this->batchJobRepository->expects(self::any())
            ->method('createJobExecution')
            ->willReturnCallback(function ($instance) {
                $execution = new JobExecution();
                $execution->setJobInstance($instance);

                return $execution;
            });
        $context = $this->mockAggregatorContext(SimpleContextAggregator::TYPE);
        $result = $this->executor->executeJob('import', 'test', $configuration);

        self::assertInstanceOf(JobResult::class, $result);
        self::assertTrue($result->isSuccessful());
        self::assertEquals($context, $result->getContext());
    }

    public function testExecuteJobSuccessInValidationModeWithTransactionStarted(): void
    {
        $configuration = ['test' => true];

        $this->connection->expects(self::never())
            ->method('getTransactionNestingLevel');

        $this->entityManager->expects(self::once())
            ->method('beginTransaction');
        $this->entityManager->expects(self::once())
            ->method('rollback');
        $this->entityManager->expects(self::never())
            ->method('commit');

        $this->batchJobManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(JobInstance::class));
        $this->batchJobManager->expects(self::once())
            ->method('flush');

        $stepExecution = $this->createMock(StepExecution::class);
        $stepExecution->expects(self::any())
            ->method('getFailureExceptions')
            ->willReturn([]);

        $job = $this->createMock(JobInterface::class);
        $job->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf(JobExecution::class))
            ->willReturnCallback(function (JobExecution $jobExecution) use ($configuration, $stepExecution) {
                self::assertEquals('import.test', $jobExecution->getJobInstance()->getLabel());
                self::assertEquals($configuration, $jobExecution->getJobInstance()->getRawConfiguration());

                $jobExecution->setStatus(new BatchStatus(BatchStatus::COMPLETED));
                $jobExecution->addStepExecution($stepExecution);
            });

        $this->batchJobRegistry->expects(self::once())
            ->method('getJob')
            ->with(self::isInstanceOf(JobInstance::class))
            ->willReturn($job);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(Events::AFTER_JOB_EXECUTION)
            ->willReturn(false);
        $dispatcher->expects(self::never())
            ->method('dispatch');

        $this->executor->setEventDispatcher($dispatcher);
        $this->batchJobRepository->expects(self::any())
            ->method('createJobExecution')
            ->willReturnCallback(function ($instance) {
                $execution = new JobExecution();
                $execution->setJobInstance($instance);

                return $execution;
            });
        $context = $this->mockAggregatorContext(SimpleContextAggregator::TYPE);
        $this->executor->setValidationMode(true);
        $result = $this->executor->executeJob('import', 'test', $configuration);

        self::assertInstanceOf(JobResult::class, $result);
        self::assertTrue($result->isSuccessful());
        self::assertEquals($context, $result->getContext());
    }

    public function testExecuteJobStopped(): void
    {
        $configuration = ['test' => true];

        $this->connection->expects(self::once())
            ->method('getTransactionNestingLevel')
            ->willReturn(0);

        $this->entityManager->expects(self::once())
            ->method('beginTransaction');
        $this->entityManager->expects(self::once())
            ->method('rollback');
        $this->entityManager->expects(self::never())
            ->method('commit');

        $this->batchJobManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(JobInstance::class));
        $this->batchJobManager->expects(self::once())
            ->method('flush');

        $job = $this->createMock(JobInterface::class);
        $job->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf(JobExecution::class))
            ->willReturnCallback(function (JobExecution $jobExecution) {
                $jobExecution->setStatus(new BatchStatus(BatchStatus::STOPPED));
            });

        $this->batchJobRegistry->expects(self::once())
            ->method('getJob')
            ->with(self::isInstanceOf(JobInstance::class))
            ->willReturn($job);
        $this->mockCreateJobExecutionWithStepExecution();
        $this->mockAggregatorContext(SimpleContextAggregator::TYPE);

        $result = $this->executor->executeJob('import', 'test', $configuration);

        self::assertInstanceOf(JobResult::class, $result);
        self::assertFalse($result->isSuccessful());
    }

    public function testExecuteJobFailure(): void
    {
        $configuration = ['test' => true];

        $this->connection->expects(self::once())
            ->method('getTransactionNestingLevel')
            ->willReturnOnConsecutiveCalls(0);

        $this->entityManager->expects(self::once())
            ->method('beginTransaction');
        $this->entityManager->expects(self::once())
            ->method('rollback');
        $this->entityManager->expects(self::never())
            ->method('commit');

        $this->batchJobManager->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(JobInstance::class));
        $this->batchJobManager->expects(self::once())
            ->method('flush');

        $job = $this->createMock(JobInterface::class);
        $job->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf(JobExecution::class))
            ->willReturnCallback(function (JobExecution $jobExecution) {
                $jobExecution->addFailureException(new \Exception('Error 1'));
                $jobExecution->setStatus(new BatchStatus(BatchStatus::FAILED));
            });

        $this->batchJobRegistry->expects(self::once())
            ->method('getJob')
            ->with(self::isInstanceOf(JobInstance::class))
            ->willReturn($job);

        $this->mockCreateJobExecutionWithStepExecution();
        $this->mockAggregatorContext(SimpleContextAggregator::TYPE);
        $result = $this->executor->executeJob('import', 'test', $configuration);

        self::assertInstanceOf(JobResult::class, $result);
        self::assertFalse($result->isSuccessful());
        self::assertNull($result->needRedelivery());
        self::assertEquals(['Error 1'], $result->getFailureExceptions());
    }

    public function testGetJobErrorsUnknownInstanceException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No job instance found with code unknown');

        $code = 'unknown';

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $code]);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(JobInstance::class)
            ->willReturn($repository);
        $this->executor->getJobErrors($code);
    }

    public function testGetJobErrorsUnknownExecutionException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No job execution found for job instance with code unknown');

        $code = 'unknown';

        $jobInstance = $this->createMock(JobInstance::class);
        $jobInstance->expects(self::once())
            ->method('getJobExecutions')
            ->willReturn(new ArrayCollection());

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $code])
            ->willReturn($jobInstance);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(JobInstance::class)
            ->willReturn($repository);
        $this->executor->getJobErrors($code);
    }

    public function testGetJobErrors(): void
    {
        $code = 'known';

        $stepExecution = $this->createMock(StepExecution::class);

        $jobExecution = $this->createMock(JobExecution::class);

        $jobExecution->expects(self::once())
            ->method('getStepExecutions')
            ->willReturn(new ArrayCollection([$stepExecution]));

        $jobInstance = $this->createMock(JobInstance::class);
        $jobInstance->expects(self::once())
            ->method('getJobExecutions')
            ->willReturn(new ArrayCollection([$jobExecution]));

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $code])
            ->willReturn($jobInstance);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(JobInstance::class)
            ->willReturn($repository);

        $context = $this->createMock(ContextInterface::class);
        $context->expects(self::once())
            ->method('getErrors')
            ->willReturn(['Error 1']);
        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->willReturn($context);

        self::assertEquals(['Error 1'], $this->executor->getJobErrors($code));
    }

    public function testGetJobFailureExceptions(): void
    {
        $code = 'known';

        $jobExecution = $this->createMock(JobExecution::class);
        $jobExecution->expects(self::once())
            ->method('getAllFailureExceptions')
            ->willReturn([['message' => 'Error 1']]);

        $jobInstance = $this->createMock(JobInstance::class);
        $jobInstance->expects(self::once())
            ->method('getJobExecutions')
            ->willReturn(new ArrayCollection([$jobExecution]));

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $code])
            ->willReturn($jobInstance);
        $this->doctrine->expects(self::once())
            ->method('getRepository')
            ->with(JobInstance::class)
            ->willReturn($repository);

        self::assertEquals(['Error 1'], $this->executor->getJobFailureExceptions($code));
    }

    private function mockAggregatorContext(string $aggregatorType): ContextInterface
    {
        $context = $this->createMock(ContextInterface::class);
        $aggregator = $this->createMock(ContextAggregatorInterface::class);
        $aggregator->expects(self::once())
            ->method('getAggregatedContext')
            ->willReturn($context);
        $this->contextAggregatorRegistry->expects(self::once())
            ->method('getAggregator')
            ->with($aggregatorType)
            ->willReturn($aggregator);

        return $context;
    }

    private function mockCreateJobExecutionWithStepExecution(): void
    {
        $this->batchJobRepository->expects(self::any())
            ->method('createJobExecution')
            ->willReturnCallback(function ($instance) {
                $execution = new JobExecution();
                $execution->setJobInstance($instance);
                $execution->addStepExecution(new StepExecution('test', $execution));

                return $execution;
            });
    }
}
