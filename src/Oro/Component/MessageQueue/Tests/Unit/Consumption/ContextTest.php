<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Exception\IllegalContextModificationException;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\NullLogger;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ContextTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithSessionAsFirstArgument(): void
    {
        new Context($this->createSession());
    }

    public function testShouldAllowGetSessionSetInConstructor(): void
    {
        $session = $this->createSession();

        $context = new Context($session);

        self::assertSame($session, $context->getSession());
    }

    public function testShouldAllowGetMessageConsumerPreviouslySet(): void
    {
        $messageConsumer = $this->createMessageConsumer();

        $context = new Context($this->createSession());
        $context->setMessageConsumer($messageConsumer);

        self::assertSame($messageConsumer, $context->getMessageConsumer());
    }

    public function testThrowOnTryToChangeMessageConsumerIfAlreadySet(): void
    {
        $messageConsumer = $this->createMessageConsumer();
        $anotherMessageConsumer = $this->createMessageConsumer();

        $context = new Context($this->createSession());

        $context->setMessageConsumer($messageConsumer);

        $this->expectException(IllegalContextModificationException::class);
        $context->setMessageConsumer($anotherMessageConsumer);
    }

    public function testShouldAllowGetMessageProcessorPreviouslySet(): void
    {
        $messageProcessorName = 'sample_processor';

        $context = new Context($this->createSession());
        $context->setMessageProcessorName($messageProcessorName);

        self::assertSame($messageProcessorName, $context->getMessageProcessorName());
    }

    public function testShouldAllowGetLoggerPreviouslySet(): void
    {
        $logger = new NullLogger();

        $context = new Context($this->createSession());
        $context->setLogger($logger);

        self::assertSame($logger, $context->getLogger());
    }

    public function testShouldSetExecutionInterruptedToFalseInConstructor(): void
    {
        $context = new Context($this->createSession());

        self::assertFalse($context->isExecutionInterrupted());
    }

    public function testShouldAllowGetPreviouslySetMessage(): void
    {
        /** @var MessageInterface $message */
        $message = $this->createMock(MessageInterface::class);

        $context = new Context($this->createSession());

        $context->setMessage($message);

        self::assertSame($message, $context->getMessage());
    }

    public function testThrowOnTryToChangeMessageIfAlreadySet(): void
    {
        /** @var MessageInterface $message */
        $message = $this->createMock(MessageInterface::class);

        $context = new Context($this->createSession());

        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The message could be set once');
        $context->setMessage($message);
        $context->setMessage($message);
    }

    public function testShouldAllowGetPreviouslySetException(): void
    {
        $exception = new \Exception();

        $context = new Context($this->createSession());

        $context->setException($exception);

        self::assertSame($exception, $context->getException());
    }

    public function testShouldAllowGetPreviouslySetStatus(): void
    {
        $status = 'aStatus';

        $context = new Context($this->createSession());

        $context->setStatus($status);

        self::assertSame($status, $context->getStatus());
    }

    public function testThrowOnTryToChangeStatusIfAlreadySet(): void
    {
        $status = 'aStatus';

        $context = new Context($this->createSession());
        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The status modification is not allowed');
        $context->setStatus($status);
        $context->setStatus($status);
    }

    public function testShouldAllowGetPreviouslySetExecutionInterrupted(): void
    {
        $context = new Context($this->createSession());

        // guard
        self::assertFalse($context->isExecutionInterrupted());

        $context->setExecutionInterrupted(true);

        self::assertTrue($context->isExecutionInterrupted());
    }

    public function testThrowOnTryToRollbackExecutionInterruptedIfAlreadySetToTrue(): void
    {
        $context = new Context($this->createSession());

        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The execution once interrupted could not be roll backed');
        $context->setExecutionInterrupted(true);
        $context->setExecutionInterrupted(false);
    }

    public function testNotThrowOnSettingExecutionInterruptedToTrueIfAlreadySetToTrue(): void
    {
        $context = new Context($this->createSession());

        $context->setExecutionInterrupted(true);
        $context->setExecutionInterrupted(true);
    }

    public function testShouldAllowGetPreviouslySetLogger(): void
    {
        $expectedLogger = new NullLogger();

        $context = new Context($this->createSession());

        $context->setLogger($expectedLogger);

        self::assertSame($expectedLogger, $context->getLogger());
    }

    public function testThrowOnSettingLoggerIfAlreadySet(): void
    {
        $context = new Context($this->createSession());

        $context->setLogger(new NullLogger());
        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The logger modification is not allowed');
        $context->setLogger(new NullLogger());
    }

    public function testShouldAllowGetPreviouslySetQueueName(): void
    {
        $context = new Context($this->createSession());

        $context->setQueueName('theQueueName');

        self::assertSame('theQueueName', $context->getQueueName());
    }

    public function testThrowOnSettingQueueNameIfAlreadySet(): void
    {
        $context = new Context($this->createSession());

        $context->setQueueName('theQueueName');
        $this->expectException(IllegalContextModificationException::class);
        $this->expectExceptionMessage('The queueName modification is not allowed');
        $context->setQueueName('theAnotherQueueName');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|SessionInterface
     */
    protected function createSession(): \PHPUnit\Framework\MockObject\MockObject|SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageConsumerInterface
     */
    protected function createMessageConsumer(): \PHPUnit\Framework\MockObject\MockObject|MessageConsumerInterface
    {
        return $this->createMock(MessageConsumerInterface::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|MessageProcessorInterface
     */
    protected function createMessageProcessor(): \PHPUnit\Framework\MockObject\MockObject|MessageProcessorInterface
    {
        return $this->createMock(MessageProcessorInterface::class);
    }
}
