<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\Exception\IllegalContextModificationException;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\Extensions;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\ClassExtensionTrait;
use Psr\Log\NullLogger;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithExpectedArguments()
    {
        new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
        );
    }

    public function testShouldAllowGetSessionSetInConstructor()
    {
        $session = $this->createSession();

        $context = new Context(
            $session,
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
        );

        $this->assertSame($session, $context->getSession());
    }

    public function testShouldAllowGetMessageConsumerSetInConstructor()
    {
        $messageConsumer = $this->createMessageConsumer();
        
        $context = new Context(
            $this->createSession(),
            $messageConsumer,
            $this->createMessageProcessor(),
            new NullLogger()
        );

        $this->assertSame($messageConsumer, $context->getMessageConsumer());
    }

    public function testShouldAllowGetMessageProducerSetInConstructor()
    {
        $messageProcessor = $this->createMessageProcessor();

        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $messageProcessor,
            new NullLogger()
        );

        $this->assertSame($messageProcessor, $context->getMessageProcessor());
    }

    public function testShouldAllowGetLoggerSetInConstructor()
    {
        $logger = new NullLogger();

        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            $logger
        );

        $this->assertSame($logger, $context->getLogger());
    }

    public function testShouldSetExecutionInterruptedToFalseInConstructor()
    {
        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
        );

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testShouldAllowGetPreviouslySetMessage()
    {
        /** @var MessageInterface $message */
        $message = $this->getMock(MessageInterface::class);

        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
        );

        $context->setMessage($message);

        $this->assertSame($message, $context->getMessage());
    }

    public function testThrowOnTryToChangeMessageIfAlreadySet()
    {
        /** @var MessageInterface $message */
        $message = $this->getMock(MessageInterface::class);

        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
        );

        $this->setExpectedException(
            IllegalContextModificationException::class,
            'The message modification is not allowed'
        );

        $context->setMessage($message);
        $context->setMessage($message);
    }

    public function testShouldAllowGetPreviouslySetException()
    {
        $exception = new \Exception();

        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
        );

        $context->setException($exception);

        $this->assertSame($exception, $context->getException());
    }

    public function testShouldAllowGetPreviouslySetStatus()
    {
        $status = 'aStatus';

        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
        );

        $context->setStatus($status);

        $this->assertSame($status, $context->getStatus());
    }

    public function testThrowOnTryToChangeStatusIfAlreadySet()
    {
        $status = 'aStatus';

        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
        );

        $this->setExpectedException(
            IllegalContextModificationException::class,
            'The status modification is not allowed'
        );

        $context->setStatus($status);
        $context->setStatus($status);
    }

    public function testShouldAllowGetPreviouslySetExecutionInterrupted()
    {
        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
        );

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        $context->setExecutionInterrupted(true);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testThrowOnTryToRollbackExecutionInterruptedIfAlreadySetToTrue()
    {
        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
        );

        $this->setExpectedException(
            IllegalContextModificationException::class,
            'The execution once interrupted could not be roll backed'
        );

        $context->setExecutionInterrupted(true);
        $context->setExecutionInterrupted(false);
    }

    public function testNotThrowOnSettingExecutionInterruptedToTrueIfAlreadySetToTrue()
    {
        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
        );

        $context->setExecutionInterrupted(true);
        $context->setExecutionInterrupted(true);
    }

    public function testShouldAllowGetPreviouslySetLogger()
    {
        $expectedLogger = new NullLogger();

        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
        );

        $context->setLogger($expectedLogger);

        $this->assertSame($expectedLogger, $context->getLogger());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    protected function createSession()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageConsumerInterface
     */
    protected function createMessageConsumer()
    {
        return $this->getMock(MessageConsumerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProcessorInterface
     */
    protected function createMessageProcessor()
    {
        return $this->getMock(MessageProcessorInterface::class);
    }
}
