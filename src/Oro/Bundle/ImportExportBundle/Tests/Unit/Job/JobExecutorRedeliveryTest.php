<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\JobInstance;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Job\BatchStatus;
use Akeneo\Bundle\BatchBundle\Job\JobInterface;
use Doctrine\DBAL\Driver\DriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorRegistry;
use Oro\Bundle\ImportExportBundle\Job\Context\SimpleContextAggregator;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Job\JobResult;

class JobExecutorRedeliveryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $entityManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $connection;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $managerRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $batchJobRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $contextRegistry;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $batchJobRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $batchJobManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $contextAggregatorRegistry;

    /** @var JobExecutor */
    protected $executor;

    protected function setUp(): void
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityManager->expects(self::any())
            ->method('getConnection')
            ->will(self::returnValue($this->connection));
        $this->managerRegistry = $this->getMockBuilder('Symfony\Bridge\Doctrine\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->batchJobRegistry = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Connector\ConnectorRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextRegistry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->managerRegistry->expects(self::any())->method('getManager')
            ->will(self::returnValue($this->entityManager));
        $this->batchJobManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->batchJobRepository = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Job\DoctrineJobRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->batchJobRepository->expects(self::any())
            ->method('getJobManager')
            ->will(self::returnValue($this->batchJobManager));
        $this->contextAggregatorRegistry = $this->createMock(ContextAggregatorRegistry::class);

        $this->executor = new JobExecutor(
            $this->batchJobRegistry,
            $this->batchJobRepository,
            $this->contextRegistry,
            $this->managerRegistry,
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

    protected function mockAggregatorContext($aggregatorType)
    {
        $context = $this->createMock('Oro\Bundle\ImportExportBundle\Context\ContextInterface');
        $aggregator = $this->createMock('Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorInterface');
        $aggregator
            ->expects(self::once())
            ->method('getAggregatedContext')
            ->willReturn($context);
        $this->contextAggregatorRegistry
            ->expects(self::once())
            ->method('getAggregator')
            ->with($aggregatorType)
            ->willReturn($aggregator);

        return $context;
    }

    protected function mockCreateJobExecutionWithStepExecution()
    {
        $this->batchJobRepository->expects(self::any())
            ->method('createJobExecution')
            ->willReturnCallback(
                function ($instance) {
                    $execution = new JobExecution();
                    $execution->setJobInstance($instance);
                    $execution->addStepExecution(new StepExecution('test', $execution));

                    return $execution;
                }
            );
    }
}
