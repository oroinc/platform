<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job;

use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;
use Akeneo\Bundle\BatchBundle\Job\BatchStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Job\Context\SimpleContextAggregator;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class JobExecutorTest extends \PHPUnit\Framework\TestCase
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

    protected function setUp()
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
        $this->contextAggregatorRegistry = $this
            ->getMockBuilder('Oro\Bundle\ImportExportBundle\Job\Context\ContextAggregatorRegistry')
            ->getMock();

        $this->executor = new JobExecutor(
            $this->batchJobRegistry,
            $this->batchJobRepository,
            $this->contextRegistry,
            $this->managerRegistry,
            $this->contextAggregatorRegistry
        );
    }

    public function testExecuteJobUnknownJob()
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
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'));
        $this->mockCreateJobExecutionWithStepExecution();
        $this->mockAggregatorContext(SimpleContextAggregator::TYPE);

        $result = $this->executor->executeJob('import', 'test');

        self::assertInstanceOf('Oro\Bundle\ImportExportBundle\Job\JobResult', $result);
        self::assertFalse($result->isSuccessful());
        self::assertEquals(['Can\'t find job "test"'], $result->getFailureExceptions());
        self::assertStringStartsWith('test_' . date('Y_m_d_H_'), $result->getJobCode());
    }

    public function testExecuteJobSuccess()
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
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'));
        $this->batchJobManager->expects(self::exactly(1))
            ->method('flush');

        $context = $this->mockAggregatorContext(SimpleContextAggregator::TYPE);
        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $stepExecution->expects(self::any())
            ->method('getFailureExceptions')
            ->will(self::returnValue([]));

        $job = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Job\JobInterface')
            ->getMock();
        $job->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobExecution'))
            ->will(
                $this->returnCallback(
                    function (JobExecution $jobExecution) use ($configuration, $stepExecution) {
                        \PHPUnit\Framework\Assert::assertEquals(
                            'import.test',
                            $jobExecution->getJobInstance()->getLabel()
                        );
                        \PHPUnit\Framework\Assert::assertEquals(
                            $configuration,
                            $jobExecution->getJobInstance()->getRawConfiguration()
                        );
                        $jobExecution->setStatus(new BatchStatus(BatchStatus::COMPLETED));
                        $jobExecution->addStepExecution($stepExecution);
                    }
                )
            );

        $this->batchJobRegistry->expects(self::once())
            ->method('getJob')
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'))
            ->will(self::returnValue($job));

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(Events::AFTER_JOB_EXECUTION)
            ->willReturn(true);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(Events::AFTER_JOB_EXECUTION, self::isInstanceOf(AfterJobExecutionEvent::class));

        $this->executor->setEventDispatcher($dispatcher);

        $this->batchJobRepository->expects(self::any())
            ->method('createJobExecution')
            ->willReturnCallback(
                function ($instance) {
                    $execution = new JobExecution();
                    $execution->setJobInstance($instance);

                    return $execution;
                }
            );
        $result = $this->executor->executeJob('import', 'test', $configuration);

        $this->assertInstanceOf('Oro\Bundle\ImportExportBundle\Job\JobResult', $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals($context, $result->getContext());
    }

    public function testExecuteJobSuccessWithTransactionStarted()
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
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'));
        $this->batchJobManager->expects(self::exactly(1))
            ->method('flush');

        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $stepExecution->expects(self::any())
            ->method('getFailureExceptions')
            ->will(self::returnValue([]));

        $job = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Job\JobInterface')
            ->getMock();
        $job->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobExecution'))
            ->will(
                $this->returnCallback(
                    function (JobExecution $jobExecution) use ($configuration, $stepExecution) {
                        \PHPUnit\Framework\Assert::assertEquals(
                            'import.test',
                            $jobExecution->getJobInstance()->getLabel()
                        );
                        \PHPUnit\Framework\Assert::assertEquals(
                            $configuration,
                            $jobExecution->getJobInstance()->getRawConfiguration()
                        );
                        $jobExecution->setStatus(new BatchStatus(BatchStatus::COMPLETED));
                        $jobExecution->addStepExecution($stepExecution);
                    }
                )
            );

        $this->batchJobRegistry->expects(self::once())
            ->method('getJob')
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'))
            ->will(self::returnValue($job));

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(Events::AFTER_JOB_EXECUTION)
            ->willReturn(false);
        $dispatcher->expects(self::never())->method('dispatch');

        $this->executor->setEventDispatcher($dispatcher);
        $this->batchJobRepository->expects(self::any())
            ->method('createJobExecution')
            ->willReturnCallback(
                function ($instance) {
                    $execution = new JobExecution();
                    $execution->setJobInstance($instance);

                    return $execution;
                }
            );
        $context = $this->mockAggregatorContext(SimpleContextAggregator::TYPE);
        $result = $this->executor->executeJob('import', 'test', $configuration);

        $this->assertInstanceOf('Oro\Bundle\ImportExportBundle\Job\JobResult', $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals($context, $result->getContext());
    }

    public function testExecuteJobSuccessInValidationModeWithTransactionStarted()
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
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'));
        $this->batchJobManager->expects(self::exactly(1))
            ->method('flush');

        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $stepExecution->expects(self::any())
            ->method('getFailureExceptions')
            ->will(self::returnValue([]));

        $job = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Job\JobInterface')
            ->getMock();
        $job->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobExecution'))
            ->will(
                $this->returnCallback(
                    function (JobExecution $jobExecution) use ($configuration, $stepExecution) {
                        \PHPUnit\Framework\Assert::assertEquals(
                            'import.test',
                            $jobExecution->getJobInstance()->getLabel()
                        );
                        \PHPUnit\Framework\Assert::assertEquals(
                            $configuration,
                            $jobExecution->getJobInstance()->getRawConfiguration()
                        );
                        $jobExecution->setStatus(new BatchStatus(BatchStatus::COMPLETED));
                        $jobExecution->addStepExecution($stepExecution);
                    }
                )
            );

        $this->batchJobRegistry->expects(self::once())
            ->method('getJob')
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'))
            ->will(self::returnValue($job));

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(Events::AFTER_JOB_EXECUTION)
            ->willReturn(false);
        $dispatcher->expects(self::never())->method('dispatch');

        $this->executor->setEventDispatcher($dispatcher);
        $this->batchJobRepository->expects(self::any())
            ->method('createJobExecution')
            ->willReturnCallback(
                function ($instance) {
                    $execution = new JobExecution();
                    $execution->setJobInstance($instance);

                    return $execution;
                }
            );
        $context = $this->mockAggregatorContext(SimpleContextAggregator::TYPE);
        $this->executor->setValidationMode(true);
        $result = $this->executor->executeJob('import', 'test', $configuration);

        $this->assertInstanceOf('Oro\Bundle\ImportExportBundle\Job\JobResult', $result);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals($context, $result->getContext());
    }

    public function testExecuteJobStopped()
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
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'));
        $this->batchJobManager->expects(self::exactly(1))
            ->method('flush');

        $job = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Job\JobInterface')
            ->getMock();
        $job->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobExecution'))
            ->will(self::returnCallback(
                function (JobExecution $jobExecution) use ($configuration) {
                    $jobExecution->setStatus(new BatchStatus(BatchStatus::STOPPED));
                }
            ));

        $this->batchJobRegistry->expects(self::once())
            ->method('getJob')
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'))
            ->will(self::returnValue($job));
        $this->mockCreateJobExecutionWithStepExecution();
        $this->mockAggregatorContext(SimpleContextAggregator::TYPE);

        $result = $this->executor->executeJob('import', 'test', $configuration);

        $this->assertInstanceOf('Oro\Bundle\ImportExportBundle\Job\JobResult', $result);
        $this->assertFalse($result->isSuccessful());
    }

    public function testExecuteJobFailure()
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
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'));
        $this->batchJobManager->expects(self::exactly(1))
            ->method('flush');

        $job = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Job\JobInterface')
            ->getMock();
        $job->expects(self::once())
            ->method('execute')
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobExecution'))
            ->will(self::returnCallback(
                function (JobExecution $jobExecution) use ($configuration) {
                    $jobExecution->addFailureException(new \Exception('Error 1'));
                    $jobExecution->setStatus(new BatchStatus(BatchStatus::FAILED));
                }
            ));

        $this->batchJobRegistry->expects(self::once())
            ->method('getJob')
            ->with(self::isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'))
            ->will(self::returnValue($job));

        $this->mockCreateJobExecutionWithStepExecution();
        $this->mockAggregatorContext(SimpleContextAggregator::TYPE);
        $result = $this->executor->executeJob('import', 'test', $configuration);

        $this->assertInstanceOf('Oro\Bundle\ImportExportBundle\Job\JobResult', $result);
        $this->assertFalse($result->isSuccessful());
        $this->assertEquals(['Error 1'], $result->getFailureExceptions());
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage No job instance found with code unknown
     */
    public function testGetJobErrorsUnknownInstanceException()
    {
        $code = 'unknown';

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $code]);
        $this->managerRegistry->expects(self::once())
            ->method('getRepository')
            ->with('AkeneoBatchBundle:JobInstance')
            ->will(self::returnValue($repository));
        $this->executor->getJobErrors($code);
    }

    /**
     * @expectedException \Oro\Bundle\ImportExportBundle\Exception\LogicException
     * @expectedExceptionMessage No job execution found for job instance with code unknown
     */
    public function testGetJobErrorsUnknownExecutionException()
    {
        $code = 'unknown';

        $jobInstance = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\JobInstance')
            ->disableOriginalConstructor()
            ->getMock();
        $jobInstance->expects(self::once())
            ->method('getJobExecutions')
            ->will(self::returnValue(new ArrayCollection()));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $code])
            ->will(self::returnValue($jobInstance));
        $this->managerRegistry->expects(self::once())
            ->method('getRepository')
            ->with('AkeneoBatchBundle:JobInstance')
            ->will(self::returnValue($repository));
        $this->executor->getJobErrors($code);
    }

    public function testGetJobErrors()
    {
        $code = 'known';

        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $jobExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\JobExecution')
            ->disableOriginalConstructor()
            ->getMock();

        $jobExecution->expects(self::once())
            ->method('getStepExecutions')
            ->will(self::returnValue([$stepExecution]));

        $jobInstance = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\JobInstance')
            ->disableOriginalConstructor()
            ->getMock();
        $jobInstance->expects(self::once())
            ->method('getJobExecutions')
            ->will(self::returnValue(new ArrayCollection([$jobExecution])));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $code])
            ->will(self::returnValue($jobInstance));
        $this->managerRegistry->expects(self::once())
            ->method('getRepository')
            ->with('AkeneoBatchBundle:JobInstance')
            ->will(self::returnValue($repository));

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')
            ->getMockForAbstractClass();
        $context->expects(self::once())
            ->method('getErrors')
            ->will(self::returnValue(['Error 1']));
        $this->contextRegistry->expects(self::once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->will(self::returnValue($context));

        $this->assertEquals(['Error 1'], $this->executor->getJobErrors($code));
    }

    public function testGetJobFailureExceptions()
    {
        $code = 'known';

        $jobExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\JobExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $jobExecution->expects(self::once())
            ->method('getAllFailureExceptions')
            ->will(self::returnValue([['message' => 'Error 1']]));

        $jobInstance = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\JobInstance')
            ->disableOriginalConstructor()
            ->getMock();
        $jobInstance->expects(self::once())
            ->method('getJobExecutions')
            ->will(self::returnValue(new ArrayCollection([$jobExecution])));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects(self::once())
            ->method('findOneBy')
            ->with(['code' => $code])
            ->will(self::returnValue($jobInstance));
        $this->managerRegistry->expects(self::once())
            ->method('getRepository')
            ->with('AkeneoBatchBundle:JobInstance')
            ->will(self::returnValue($repository));

        $this->assertEquals(['Error 1'], $this->executor->getJobFailureExceptions($code));
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
