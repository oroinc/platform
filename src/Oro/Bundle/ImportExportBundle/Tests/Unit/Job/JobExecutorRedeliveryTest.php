<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
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
use Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorInterface;
use Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorRegistry;
use Oro\Bundle\ImportExportBundle\Job\Context\SimpleContextAggregator;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Job\JobResult;

class JobExecutorRedeliveryTest extends \PHPUnit\Framework\TestCase
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

    /** @dataProvider getRedeliveryExceptions */
    public function testExecuteJobRedelivered($exception): void
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
            ->willReturnCallback(function (JobExecution $jobExecution) use ($exception) {
                $jobExecution->addFailureException($exception);
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
        self::assertTrue($result->needRedelivery());
        self::assertEmpty($result->getFailureExceptions());
    }

    public function getRedeliveryExceptions(): array
    {
        $driverException = $this->createMock(DriverException::class);

        return [
            'UniqueConstraintViolationException' => [
                new UniqueConstraintViolationException('Error 1', $driverException)
            ],
            'DeadlockException' => [
                new DeadlockException('Error 2', $driverException),
            ]
        ];
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
