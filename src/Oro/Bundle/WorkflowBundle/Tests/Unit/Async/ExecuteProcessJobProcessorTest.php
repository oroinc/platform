<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Async\ExecuteProcessJobProcessor;
use Oro\Bundle\WorkflowBundle\Async\Topics;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
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
            $this->createDoctrineHelper(),
            $this->createMock(ProcessHandler::class),
            $this->createMock(LoggerInterface::class)
        );
    }

    public function testThrowIfMessageBodyIsNotValidJson()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $processor = new ExecuteProcessJobProcessor(
            $this->createDoctrineHelper(),
            $this->createMock(ProcessHandler::class),
            $logger
        );

        $message = new Message();
        $message->setBody('{]');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The malformed json given.');

        $session = $this->createMock(SessionInterface::class);
        $processor->process($message, $session);
    }

    public function testShouldRejectMessageIfProcessJobIdNotSet()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(self::once())
            ->method('critical')
            ->with('Process Job Id not set');

        $processor = new ExecuteProcessJobProcessor(
            $this->createDoctrineHelper(),
            $this->createMock(ProcessHandler::class),
            $logger
        );

        $message = new Message();
        $message->setBody('{}');

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfProcessJobIdWithSuchIdNotFound()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method('find')
            ->with(ProcessJob::class, 'theProcessJobId')
            ->willReturn(null);

        $doctrineHelper = $this->createDoctrineHelper($entityManager);

        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(self::once())
            ->method('critical');

        $processor = new ExecuteProcessJobProcessor(
            $doctrineHelper,
            $this->createMock(ProcessHandler::class),
            $logger
        );

        $message = new Message();
        $message->setBody(JSON::encode(['process_job_id' => 'theProcessJobId']));

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfEntityManagerNotExist()
    {
        $doctrineHelper = $this->createDoctrineHelper();

        $processHandle = $this->createMock(ProcessHandler::class);

        $logger = $this->createMock(LoggerInterface::class);

        $logger->expects(self::once())
            ->method('critical');

        $processor = new ExecuteProcessJobProcessor(
            $doctrineHelper,
            $processHandle,
            $logger
        );

        $message = new Message();
        $message->setBody(JSON::encode(['process_job_id' => 'theProcessJobId']));

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldExecuteProcessJob()
    {
        $processJob = new ProcessJob();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('commit');

        $entityManager->expects($this->once())
            ->method('find')
            ->with(ProcessJob::class, 'theProcessJobId')
            ->willReturn($processJob);

        $entityManager->expects($this->once())
            ->method('contains')
            ->with($processJob)
            ->willReturn(true);

        $processHandle = $this->createMock(ProcessHandler::class);
        $processHandle->expects(self::once())
            ->method('handleJob')
            ->with($processJob);

        $processHandle->expects($this->once())
            ->method('finishJob')
            ->with($processJob);

        $doctrineHelper = $this->createDoctrineHelper($entityManager);

        $processor = new ExecuteProcessJobProcessor(
            $doctrineHelper,
            $processHandle,
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody(JSON::encode(['process_job_id' => 'theProcessJobId']));

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldRefreshProcessJobOnDetach()
    {
        $id = 123;
        $processJob = $this->getEntity(ProcessJob::class, ['id' => $id]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('commit');

        $entityManager->expects($this->exactly(2))
            ->method('find')
            ->with(ProcessJob::class, $id)
            ->willReturn($processJob);

        $entityManager->expects($this->once())
            ->method('contains')
            ->with($processJob)
            ->willReturn(false);

        $processHandle = $this->createMock(ProcessHandler::class);
        $processHandle->expects($this->once())
            ->method('handleJob')
            ->with($processJob);
        $processHandle->expects($this->once())
            ->method('finishJob')
            ->with($processJob);

        $doctrineHelper = $this->createDoctrineHelper($entityManager);

        $processor = new ExecuteProcessJobProcessor(
            $doctrineHelper,
            $processHandle,
            $this->createMock(LoggerInterface::class)
        );

        $message = new Message();
        $message->setBody(JSON::encode(['process_job_id' => $id]));

        $session = $this->createMock(SessionInterface::class);
        $status = $processor->process($message, $session);

        $this->assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testFinishJobOnException()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error');

        $processJobId = 123;
        $processJob = new ProcessJob();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $doctrineHelper = $this->createDoctrineHelper($entityManager);
        $processHandle = $this->createMock(ProcessHandler::class);
        $logger = $this->createMock(LoggerInterface::class);

        $entityManager->expects($this->once())
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

        $message = new Message();
        $message->setBody(JSON::encode(['process_job_id' => $processJobId]));

        $logger->expects($this->once())
            ->method('error');

        $processor = new ExecuteProcessJobProcessor($doctrineHelper, $processHandle, $logger);

        $session = $this->createMock(SessionInterface::class);
        $processor->process($message, $session);
    }

    private function createDoctrineHelper(EntityManagerInterface $entityManager = null): DoctrineHelper
    {
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($entityManager);

        return $doctrineHelper;
    }
}
