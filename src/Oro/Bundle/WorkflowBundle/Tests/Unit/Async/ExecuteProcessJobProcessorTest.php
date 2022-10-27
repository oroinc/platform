<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Async;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WorkflowBundle\Async\ExecuteProcessJobProcessor;
use Oro\Bundle\WorkflowBundle\Async\Topic\ExecuteProcessJobTopic;
use Oro\Bundle\WorkflowBundle\Entity\ProcessJob;
use Oro\Bundle\WorkflowBundle\Model\ProcessHandler;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExecuteProcessJobProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;
    use ClassExtensionTrait;
    use EntityTrait;

    private EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $entityManager;

    private ProcessHandler|\PHPUnit\Framework\MockObject\MockObject $processHandler;

    private ExecuteProcessJobProcessor $processor;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects(self::any())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->processHandler = $this->createMock(ProcessHandler::class);
        $this->processor = new ExecuteProcessJobProcessor(
            $doctrineHelper,
            $this->processHandler
        );

        $this->setUpLoggerMock($this->processor);
    }

    public function testShouldImplementMessageProcessorInterface(): void
    {
        $this->assertClassImplements(MessageProcessorInterface::class, ExecuteProcessJobProcessor::class);
    }

    public function testShouldImplementTopicSubscriberInterface(): void
    {
        $this->assertClassImplements(TopicSubscriberInterface::class, ExecuteProcessJobProcessor::class);
    }

    public function testShouldSubscribeOnExecuteProcessJobTopic(): void
    {
        self::assertEquals([ExecuteProcessJobTopic::getName()], ExecuteProcessJobProcessor::getSubscribedTopics());
    }

    public function testCouldBeConstructedWithDoctrineHelperAndProcessHandler(): void
    {
        $processor = new ExecuteProcessJobProcessor(
            $this->createMock(DoctrineHelper::class),
            $this->createMock(ProcessHandler::class)
        );
        $processor->setLogger($this->createMock(LoggerInterface::class));
    }

    public function testShouldRejectMessageIfProcessJobIdWithSuchIdNotFound(): void
    {
        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(ProcessJob::class, 42)
            ->willReturn(null);

        $this->loggerMock->expects(self::once())
            ->method('critical');

        $message = new Message();
        $message->setBody(['process_job_id' => 42]);

        $session = $this->createMock(SessionInterface::class);
        $status = $this->processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldRejectMessageIfEntityManagerNotExist(): void
    {
        $this->loggerMock->expects(self::once())
            ->method('critical');

        $message = new Message();
        $message->setBody(['process_job_id' => 42]);

        $session = $this->createMock(SessionInterface::class);
        $status = $this->processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::REJECT, $status);
    }

    public function testShouldExecuteProcessJob(): void
    {
        $processJob = new ProcessJob();

        $this->entityManager->expects(self::once())
            ->method('commit');

        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(ProcessJob::class, 42)
            ->willReturn($processJob);

        $this->entityManager->expects(self::once())
            ->method('contains')
            ->with($processJob)
            ->willReturn(true);

        $this->processHandler->expects(self::once())
            ->method('handleJob')
            ->with($processJob);

        $this->processHandler->expects(self::once())
            ->method('finishJob')
            ->with($processJob);

        $message = new Message();
        $message->setBody(['process_job_id' => 42]);

        $session = $this->createMock(SessionInterface::class);
        $status = $this->processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testShouldRefreshProcessJobOnDetach(): void
    {
        $id = 123;
        $processJob = $this->getEntity(ProcessJob::class, ['id' => $id]);

        $this->entityManager->expects(self::once())
            ->method('commit');

        $this->entityManager->expects(self::exactly(2))
            ->method('find')
            ->with(ProcessJob::class, $id)
            ->willReturn($processJob);

        $this->entityManager->expects(self::once())
            ->method('contains')
            ->with($processJob)
            ->willReturn(false);

        $this->processHandler->expects(self::once())
            ->method('handleJob')
            ->with($processJob);
        $this->processHandler->expects(self::once())
            ->method('finishJob')
            ->with($processJob);

        $message = new Message();
        $message->setBody(['process_job_id' => $id]);

        $session = $this->createMock(SessionInterface::class);
        $status = $this->processor->process($message, $session);

        self::assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testFinishJobOnException(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('some error');

        $processJobId = 123;
        $processJob = new ProcessJob();

        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(ProcessJob::class, $processJobId)
            ->willReturn($processJob);

        $this->processHandler->expects(self::once())
            ->method('handleJob')
            ->with(self::identicalTo($processJob))
            ->willThrowException(new \Exception('some error'));
        $this->processHandler->expects(self::once())
            ->method('finishJob')
            ->with(self::identicalTo($processJob));

        $this->entityManager->expects(self::once())
            ->method('beginTransaction');
        $this->entityManager->expects(self::once())
            ->method('rollback');
        $this->entityManager->expects(self::never())
            ->method('commit');

        $message = new Message();
        $message->setBody(['process_job_id' => $processJobId]);

        $this->loggerMock->expects(self::once())
            ->method('error');

        $session = $this->createMock(SessionInterface::class);
        $this->processor->process($message, $session);
    }
}
