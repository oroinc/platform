<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Job;

use Doctrine\Common\Collections\ArrayCollection;

use Akeneo\Bundle\BatchBundle\Job\BatchStatus;
use Akeneo\Bundle\BatchBundle\Entity\JobExecution;
use Akeneo\Bundle\BatchBundle\Entity\StepExecution;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\ImportExportBundle\Job\Context\SimpleContextAggregator;
use Oro\Bundle\ImportExportBundle\Event\AfterJobExecutionEvent;
use Oro\Bundle\ImportExportBundle\Event\Events;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;

class JobExecutorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $connection;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $managerRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $batchJobRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $batchJobRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $batchJobManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $contextAggregatorRegistry;

    /**
     * @var JobExecutor
     */
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
            $this->managerRegistry
        );
        $this->executor->setContextAggregatorRegistry($this->contextAggregatorRegistry);
    }

    public function testExecuteJobUnknownJob()
    {
        $this->connection->expects(self::once())
            ->method('getTransactionNestingLevel')
            ->will(self::returnValue(0));
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
        $configuration = array('test' => true);
        $this->connection->expects($this->once())
            ->method('getTransactionNestingLevel')
            ->will($this->returnValue(0));
        $this->entityManager->expects($this->once())
            ->method('beginTransaction');
        $this->entityManager->expects($this->never())
            ->method('rollback');
        $this->entityManager->expects($this->once())
            ->method('commit');

        $this->batchJobManager->expects($this->once())->method('persist')
            ->with($this->isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'));
        $this->batchJobManager->expects($this->once())->method('flush')
            ->with();

        $context = $this->mockAggregatorContext(SimpleContextAggregator::TYPE);
        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $stepExecution->expects($this->any())
            ->method('getFailureExceptions')
            ->will($this->returnValue([]));

        $job = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Job\JobInterface')
            ->getMock();
        $job->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobExecution'))
            ->will(
                $this->returnCallback(
                    function (JobExecution $jobExecution) use ($configuration, $stepExecution) {
                        \PHPUnit_Framework_Assert::assertEquals(
                            'import.test',
                            $jobExecution->getJobInstance()->getLabel()
                        );
                        \PHPUnit_Framework_Assert::assertEquals(
                            $configuration,
                            $jobExecution->getJobInstance()->getRawConfiguration()
                        );
                        $jobExecution->setStatus(new BatchStatus(BatchStatus::COMPLETED));
                        $jobExecution->addStepExecution($stepExecution);
                    }
                )
            );

        $this->batchJobRegistry->expects($this->once())
            ->method('getJob')
            ->with($this->isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'))
            ->will($this->returnValue($job));

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(Events::AFTER_JOB_EXECUTION)
            ->willReturn(true);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(Events::AFTER_JOB_EXECUTION, $this->isInstanceOf(AfterJobExecutionEvent::class));

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
        $configuration = array('test' => true);
        $this->connection->expects($this->once())
            ->method('getTransactionNestingLevel')
            ->will($this->returnValue(1));
        $this->entityManager->expects($this->never())
            ->method('beginTransaction');
        $this->entityManager->expects($this->never())
            ->method('rollback');
        $this->entityManager->expects($this->never())
            ->method('commit');

        $this->batchJobManager->expects($this->once())->method('persist')
            ->with($this->isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'));
        $this->batchJobManager->expects($this->once())->method('flush')
            ->with();
        $stepExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\StepExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $stepExecution->expects($this->any())
            ->method('getFailureExceptions')
            ->will($this->returnValue(array()));

        $job = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Job\JobInterface')
            ->getMock();
        $job->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobExecution'))
            ->will(
                $this->returnCallback(
                    function (JobExecution $jobExecution) use ($configuration, $stepExecution) {
                        \PHPUnit_Framework_Assert::assertEquals(
                            'import.test',
                            $jobExecution->getJobInstance()->getLabel()
                        );
                        \PHPUnit_Framework_Assert::assertEquals(
                            $configuration,
                            $jobExecution->getJobInstance()->getRawConfiguration()
                        );
                        $jobExecution->setStatus(new BatchStatus(BatchStatus::COMPLETED));
                        $jobExecution->addStepExecution($stepExecution);
                    }
                )
            );

        $this->batchJobRegistry->expects($this->once())
            ->method('getJob')
            ->with($this->isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'))
            ->will($this->returnValue($job));

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('hasListeners')
            ->with(Events::AFTER_JOB_EXECUTION)
            ->willReturn(false);
        $dispatcher->expects($this->never())->method('dispatch');

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

    public function testExecuteJobStopped()
    {
        $configuration = array('test' => true);
        $this->connection->expects($this->once())
            ->method('getTransactionNestingLevel')
            ->will($this->returnValue(0));
        $this->entityManager->expects($this->once())
            ->method('beginTransaction');
        $this->entityManager->expects($this->once())
            ->method('rollback');
        $this->entityManager->expects($this->never())
            ->method('commit');

        $this->batchJobManager->expects($this->once())->method('persist')
            ->with($this->isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'));
        $this->batchJobManager->expects($this->once())->method('flush')
            ->with();

        $job = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Job\JobInterface')
            ->getMock();
        $job->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobExecution'))
            ->will(
                $this->returnCallback(
                    function (JobExecution $jobExecution) use ($configuration) {
                        $jobExecution->setStatus(new BatchStatus(BatchStatus::STOPPED));
                    }
                )
            );

        $this->batchJobRegistry->expects($this->once())
            ->method('getJob')
            ->with($this->isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'))
            ->will($this->returnValue($job));
        $this->mockCreateJobExecutionWithStepExecution();
        $this->mockAggregatorContext(SimpleContextAggregator::TYPE);
        $result = $this->executor->executeJob('import', 'test', $configuration);
        $this->assertInstanceOf('Oro\Bundle\ImportExportBundle\Job\JobResult', $result);
        $this->assertFalse($result->isSuccessful());
    }

    public function testExecuteJobFailure()
    {
        $configuration = array('test' => true);
        $this->connection->expects($this->once())
            ->method('getTransactionNestingLevel')
            ->will($this->returnValue(0));
        $this->entityManager->expects($this->once())
            ->method('beginTransaction');
        $this->entityManager->expects($this->once())
            ->method('rollback');
        $this->entityManager->expects($this->never())
            ->method('commit');

        $this->batchJobManager->expects($this->once())->method('persist')
            ->with($this->isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'));
        $this->batchJobManager->expects($this->once())->method('flush')
            ->with();

        $job = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Job\JobInterface')
            ->getMock();
        $job->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobExecution'))
            ->will(
                $this->returnCallback(
                    function (JobExecution $jobExecution) use ($configuration) {
                        $jobExecution->addFailureException(new \Exception('Error 1'));
                        $jobExecution->setStatus(new BatchStatus(BatchStatus::FAILED));
                    }
                )
            );

        $this->batchJobRegistry->expects($this->once())
            ->method('getJob')
            ->with($this->isInstanceOf('Akeneo\Bundle\BatchBundle\Entity\JobInstance'))
            ->will($this->returnValue($job));
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
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(array('code' => $code));
        $this->managerRegistry->expects($this->once())
            ->method('getRepository')
            ->with('AkeneoBatchBundle:JobInstance')
            ->will($this->returnValue($repository));
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
        $jobInstance->expects($this->once())
            ->method('getJobExecutions')
            ->will($this->returnValue(new ArrayCollection()));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(array('code' => $code))
            ->will($this->returnValue($jobInstance));
        $this->managerRegistry->expects($this->once())
            ->method('getRepository')
            ->with('AkeneoBatchBundle:JobInstance')
            ->will($this->returnValue($repository));
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

        $jobExecution->expects($this->once())
            ->method('getStepExecutions')
            ->will($this->returnValue(array($stepExecution)));

        $jobInstance = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\JobInstance')
            ->disableOriginalConstructor()
            ->getMock();
        $jobInstance->expects($this->once())
            ->method('getJobExecutions')
            ->will($this->returnValue(new ArrayCollection(array($jobExecution))));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(array('code' => $code))
            ->will($this->returnValue($jobInstance));
        $this->managerRegistry->expects($this->once())
            ->method('getRepository')
            ->with('AkeneoBatchBundle:JobInstance')
            ->will($this->returnValue($repository));

        $context = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Context\ContextInterface')
            ->getMockForAbstractClass();
        $context->expects($this->once())
            ->method('getErrors')
            ->will($this->returnValue(array('Error 1')));
        $this->contextRegistry->expects($this->once())
            ->method('getByStepExecution')
            ->with($stepExecution)
            ->will($this->returnValue($context));

        $this->assertEquals(array('Error 1'), $this->executor->getJobErrors($code));
    }

    public function testGetJobFailureExceptions()
    {
        $code = 'known';

        $jobExecution = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\JobExecution')
            ->disableOriginalConstructor()
            ->getMock();
        $jobExecution->expects($this->once())
            ->method('getAllFailureExceptions')
            ->will($this->returnValue(array(array('message' => 'Error 1'))));

        $jobInstance = $this->getMockBuilder('Akeneo\Bundle\BatchBundle\Entity\JobInstance')
            ->disableOriginalConstructor()
            ->getMock();
        $jobInstance->expects($this->once())
            ->method('getJobExecutions')
            ->will($this->returnValue(new ArrayCollection(array($jobExecution))));

        $repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(array('code' => $code))
            ->will($this->returnValue($jobInstance));
        $this->managerRegistry->expects($this->once())
            ->method('getRepository')
            ->with('AkeneoBatchBundle:JobInstance')
            ->will($this->returnValue($repository));

        $this->assertEquals(array('Error 1'), $this->executor->getJobFailureExceptions($code));
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
