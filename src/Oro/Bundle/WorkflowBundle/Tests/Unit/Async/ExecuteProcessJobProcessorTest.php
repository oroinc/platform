<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Async\ExecuteProcessJobProcessor;
use Oro\Bundle\WorkflowBundle\Async\Topics;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Util\JSON;
use Oro\Component\Testing\ClassExtensionTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExecuteProcessJobProcessorTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;
    use EntityTrait;

    public function testShouldImplementMessageProcessorInterface()
    {
        $this->assertClassImplements(MessageProcessorInterface::class, ExecuteProcessJobProcessor::class);
    }

    public function testShouldImplementTopicSubscriberInterface()
    {
        $this->assertClassImplements(TopicSubscriberInterface::class, ExecuteProcessJobProcessor::class);
    }

    public function testShouldSubscribeOnExecuteProcessJobTopic()
    {
        self::assertEquals([Topics::EXECUTE_PROCESS_JOB], ExecuteProcessJobProcessor::getSubscribedTopics());
    }

    public function testCouldBeConstructedWithDoctrineHelperAndProcessHandler()
    {
        new ExecuteProcessJobProcessor(
            $this->createDoctrineHelperStub(),
            $this->createProcessHandlerMock(),
            $this->createLoggerMock()
        );
    }

    public function testThrowIfMessageBodyIsNotValidJson()
    {
        $logger = $this->createLoggerMock();

        $processor = new ExecuteProcessJobProcessor(
            $this->createDoctrineHelperStub(),
            $this->createProcessHandlerMock(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody('{]');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The malformed json given.');
        $processor->process($message, new NullSession());
    }

    public function testShouldRejectMessageIfProcessJobIdNotSet()
    {
        $logger = $this->createLoggerMock();

        $logger
            ->expects(self::once())
            ->method('critical')
            ->with('Process Job Id not set');

        $processor = new ExecuteProcessJobProcessor(
            $this->createDoctrineHelperStub(),
            $this->createProcessHandlerMock(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody('{}');

        $status = $processor->process($message, new NullSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfProcessJobIdWithSuchIdNotFound()
    {
        $entityManager = $this->createEntityManagerMock();
        $entityManager
            ->expects($this->once())
            ->method('find')
            ->with(ProcessJob::class, 'theProcessJobId')
            ->willReturn(null);

        $doctrineHelper = $this->createDoctrineHelperStub($entityManager);

        $logger = $this->createLoggerMock();

        $logger
            ->expects(self::once())
            ->method('critical');

        $processor = new ExecuteProcessJobProcessor(
            $doctrineHelper,
            $this->createProcessHandlerMock(),
            $logger
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['process_job_id' => 'theProcessJobId']));

        $status = $processor->process($message, new NullSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfEntityManagerNotExist()
    {
        $doctrineHelper = $this->createDoctrineHelperStub();

        $processHandle = $this->createProcessHandlerMock();

        $logger = $this->createLoggerMock();

        $logger
            ->expects(self::once())
            ->method('critical');

        $processor = new ExecuteProcessJobProcessor(
            $doctrineHelper,
            $processHandle,
            $logger
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['process_job_id' => 'theProcessJobId']));

        $status = $processor->process($message, new NullSession());

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldExecuteProcessJob()
    {
        $processJob = new ProcessJob();

        $entityManager = $this->createEntityManagerMock();
        $entityManager
            ->expects(self::once())
            ->method('commit');

        $entityManager
            ->expects($this->once())
            ->method('find')
            ->with(ProcessJob::class, 'theProcessJobId')
            ->willReturn($processJob);

        $entityManager->expects($this->once())
            ->method('contains')
            ->with($processJob)
            ->willReturn(true);

        $processHandle = $this->createProcessHandlerMock();
        $processHandle
            ->expects(self::once())
            ->method('handleJob')
            ->with($processJob);

        $processHandle
            ->expects($this->once())
            ->method('finishJob')
            ->with($processJob);

        $doctrineHelper = $this->createDoctrineHelperStub($entityManager);

        $processor = new ExecuteProcessJobProcessor(
            $doctrineHelper,
            $processHandle,
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['process_job_id' => 'theProcessJobId']));

        $status = $processor->process($message, new NullSession());

        self::assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldRefreshProcessJobOnDetach()
    {
        $id = 123;
        $processJob = $this->getEntity(ProcessJob::class, ['id' => $id]);

        $entityManager = $this->createEntityManagerMock();
        $entityManager
            ->expects(self::once())
            ->method('commit');

        $entityManager->expects($this->exactly(2))
            ->method('find')
            ->with(ProcessJob::class, $id)
            ->willReturn($processJob);

        $entityManager->expects($this->once())
            ->method('contains')
            ->with($processJob)
            ->willReturn(false);

        $processHandle = $this->createProcessHandlerMock();
        $processHandle->expects($this->once())
            ->method('handleJob')
            ->with($processJob);
        $processHandle->expects($this->once())
            ->method('finishJob')
            ->with($processJob);

        $doctrineHelper = $this->createDoctrineHelperStub($entityManager);

        $processor = new ExecuteProcessJobProcessor(
            $doctrineHelper,
            $processHandle,
            $this->createLoggerMock()
        );

        $message = new NullMessage();
        $message->setBody(JSON::encode(['process_job_id' => $id]));

        $status = $processor->process($message, new NullSession());

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage some error
     */
    public function testFinishJobOnException()
    {
        $processJobId = 123;
        $processJob = new ProcessJob();

        $entityManager = $this->createEntityManagerMock();
        $doctrineHelper = $this->createDoctrineHelperStub($entityManager);
        $processHandle = $this->createProcessHandlerMock();
        $logger = $this->createLoggerMock();

        $entityManager
            ->expects($this->once())
            ->method('find')
            ->with(ProcessJob::class, $processJobId)
            ->willReturn($processJob);

        $processHandle->expects(self::once())
            ->method('handleJob')
            ->with(self::identicalTo($processJob))
            ->willThrowException(new \Exception('some error'));
        $processHandle->expects(self::once())
            ->method('finishJob')
            ->with(self::identicalTo($processJob));

        $entityManager->expects(self::once())
            ->method('beginTransaction');
        $entityManager->expects(self::once())
            ->method('rollback');
        $entityManager->expects(self::never())
            ->method('commit');

        $message = new NullMessage();
        $message->setBody(JSON::encode(['process_job_id' => $processJobId]));

        $logger->expects($this->once())
            ->method('error');

        $processor = new ExecuteProcessJobProcessor($doctrineHelper, $processHandle, $logger);
        $processor->process($message, new NullSession());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ProcessHandler
     */
    private function createProcessHandlerMock()
    {
        return $this->createMock(ProcessHandler::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EntityManagerInterface
     */
    private function createEntityManagerMock()
    {
        return $this->createMock(EntityManagerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|EntityRepository
     */
    private function createEntityRepositoryMock()
    {
        return $this->createMock(EntityRepository::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @param null $entityManager
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper
     */
    private function createDoctrineHelperStub($entityManager = null)
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper
            ->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        return $doctrineHelper;
    }
}
