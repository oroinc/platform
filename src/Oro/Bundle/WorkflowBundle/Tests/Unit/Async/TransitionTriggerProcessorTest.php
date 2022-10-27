<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Async;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerProcessor;
use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;
use Oro\Bundle\WorkflowBundle\Handler\TransitionTriggerHandlerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class TransitionTriggerProcessorTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private const TRIGGER_ID = 42;
    private const MAIN_ENTITY_ID = 105;

    private ObjectManager|\PHPUnit\Framework\MockObject\MockObject $objectManager;

    private TransitionTriggerHandlerInterface|\PHPUnit\Framework\MockObject\MockObject $handler;

    private TransitionTriggerProcessor $processor;

    private SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session;

    protected function setUp(): void
    {
        $this->objectManager = $this->createMock(ObjectManager::class);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($this->objectManager);

        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->handler = $this->createMock(TransitionTriggerHandlerInterface::class);

        $this->processor = new TransitionTriggerProcessor($registry, $this->handler);
        $this->setUpLoggerMock($this->processor);

        $this->session = $this->createMock(SessionInterface::class);
    }

    public function testProcess(): void
    {
        $trigger = $this->getTriggerMock();
        $message = TransitionTriggerMessage::create($trigger, self::MAIN_ENTITY_ID);

        $this->setUpObjectManager($trigger);
        $this->assertLoggerNotCalled();

        $this->handler->expects(self::once())
            ->method('process')
            ->with($trigger, $message)
            ->willReturn(true);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($this->getMessageMock(), $this->session)
        );
    }

    public function testProcessTransitionNotAllowed(): void
    {
        $trigger = $this->getTriggerMock();
        $message = $this->getMessageMock();

        $this->setUpObjectManager($trigger);

        $this->loggerMock->expects(self::once())
            ->method('warning')
            ->with('Transition not allowed', ['trigger' => $trigger]);

        $this->handler->expects(self::once())
            ->method('process')
            ->willReturn(false);

        self::assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    private function getTriggerMock(): BaseTransitionTrigger|\PHPUnit\Framework\MockObject\MockObject
    {
        $trigger = $this->createMock(BaseTransitionTrigger::class);
        $trigger->expects(self::any())
            ->method('getId')
            ->willReturn(self::TRIGGER_ID);

        return $trigger;
    }

    private function getMessageMock(array $data = null): Message
    {
        if (null === $data) {
            $data = [
                TransitionTriggerMessage::TRANSITION_TRIGGER => self::TRIGGER_ID,
                TransitionTriggerMessage::MAIN_ENTITY => self::MAIN_ENTITY_ID,
            ];
        }

        $message = new Message();
        $message->setBody($data);

        return $message;
    }

    private function setUpObjectManager(BaseTransitionTrigger $trigger = null): void
    {
        $this->objectManager->expects(self::any())
            ->method('find')
            ->with(BaseTransitionTrigger::class, self::TRIGGER_ID)
            ->willReturn($trigger);
    }
}
