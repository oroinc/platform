<?php
namespace Oro\Component\Messaging\Tests\Consumption;

use Oro\Component\Messaging\Consumption\Context;
use Oro\Component\Messaging\Consumption\Extension;
use Oro\Component\Messaging\Consumption\Extensions;
use Oro\Component\Messaging\Consumption\MessageProcessor;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\MessageConsumer;
use Oro\Component\Messaging\Transport\Session;
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
        /** @var Message $message */
        $message = $this->getMock(Message::class);

        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
        );

        $context->setMessage($message);

        $this->assertSame($message, $context->getMessage());
    }

    /**
     * @expectedException \Oro\Component\Messaging\Consumption\Exception\IllegalContextModificationException
     * @expectedExceptionMessage The message modification is not allowed
     */
    public function testThrowOnTryToChangeMessageIfAlreadySet()
    {
        /** @var Message $message */
        $message = $this->getMock(Message::class);

        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
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

    /**
     * @expectedException \Oro\Component\Messaging\Consumption\Exception\IllegalContextModificationException
     * @expectedExceptionStatus The status modification is not allowed
     */
    public function testThrowOnTryToChangeStatusIfAlreadySet()
    {
        $status = 'aStatus';

        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
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

    /**
     * @expectedException \Oro\Component\Messaging\Consumption\Exception\IllegalContextModificationException
     * @expectedExceptionMessage The execution once interrupted could not be roll backed
     */
    public function testThrowOnTryToRollbackExecutionInterruptedIfAlreadySetToTrue()
    {
        $context = new Context(
            $this->createSession(),
            $this->createMessageConsumer(),
            $this->createMessageProcessor(),
            new NullLogger()
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
     * @return \PHPUnit_Framework_MockObject_MockObject|Session
     */
    protected function createSession()
    {
        return $this->getMock(Session::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageConsumer
     */
    protected function createMessageConsumer()
    {
        return $this->getMock(MessageConsumer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProcessor
     */
    protected function createMessageProcessor()
    {
        return $this->getMock(MessageProcessor::class);
    }
}
