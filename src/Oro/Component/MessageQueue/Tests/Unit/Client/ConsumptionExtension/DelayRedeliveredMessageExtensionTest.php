<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Client\ConsumptionExtension\DelayRedeliveredMessageExtension;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Null\NullMessage;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class DelayRedeliveredMessageExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new DelayRedeliveredMessageExtension($this->createDriverMock(), 12345);
    }

    public function testShouldSendDelayedMessageAndRejectOriginalMessage()
    {
        $queue = new NullQueue('queue');

        $originMessage = new NullMessage();
        $originMessage->setRedelivered(true);
        $originMessage->setBody('theBody');
        $originMessage->setHeaders(['foo' => 'fooVal']);
        $originMessage->setProperties(['bar' => 'barVal']);

        /** @var Message $delayedMessage */
        $delayedMessage = null;

        $driver = $this->createDriverMock();
        $driver
            ->expects(self::once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->isInstanceOf(Message::class))
            ->willReturnCallback(function ($queue, $message) use (&$delayedMessage) {
                $delayedMessage = $message;
            })
        ;

        $session = $this->createSessionMock();
        $session
            ->expects(self::once())
            ->method('createQueue')
            ->with('queue')
            ->will($this->returnValue($queue))
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->at(0))
            ->method('debug')
            ->with('[DelayRedeliveredMessageExtension] Send delayed message')
        ;
        $logger
            ->expects($this->at(1))
            ->method('debug')
            ->with(
                '[DelayRedeliveredMessageExtension] '.
                'Reject redelivered original message by setting reject status to context.'
            )
        ;

        $context = new Context($session);
        $context->setQueueName('queue');
        $context->setMessage($originMessage);
        $context->setLogger($logger);

        self::assertNull($context->getStatus());

        $extension = new DelayRedeliveredMessageExtension($driver, 12345);
        $extension->onPreReceived($context);

        self::assertEquals(MessageProcessorInterface::REJECT, $context->getStatus());

        self::assertInstanceOf(Message::class, $delayedMessage);
        self::assertEquals('theBody', $delayedMessage->getBody());
        self::assertEquals(['foo' => 'fooVal'], $delayedMessage->getHeaders());
        self::assertEquals([
            'bar' => 'barVal',
            'oro-redeliver-count' => 1,
        ], $delayedMessage->getProperties());
    }

    public function testShouldDoNothingIfMessageIsNotRedelivered()
    {
        $message = new NullMessage();

        $driver = $this->createDriverMock();
        $driver
            ->expects(self::never())
            ->method('send')
        ;

        $context = new Context($this->createSessionMock());
        $context->setQueueName('queue');
        $context->setMessage($message);

        $extension = new DelayRedeliveredMessageExtension($driver, 12345);
        $extension->onPreReceived($context);

        self::assertNull($context->getStatus());
    }

    public function testShouldAddRedeliverCountHeaderAndRemoveItAfterDelayFromOriginalMessage()
    {
        $queue = new NullQueue('queue');

        $message = new NullMessage();
        $message->setRedelivered(true);

        $driver = $this->createDriverMock();
        $driver
            ->expects(self::once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->isInstanceOf(Message::class))
            ->will($this->returnCallback(function (QueueInterface $queue, Message $message) {
                $properties = $message->getProperties();
                self::assertArrayHasKey(DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT, $properties);
                self::assertSame(1, $properties[DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT]);
            }))
        ;

        $session = $this->createSessionMock();
        $session
            ->expects(self::once())
            ->method('createQueue')
            ->with('queue')
            ->will($this->returnValue($queue))
        ;

        $context = new Context($session);
        $context->setQueueName('queue');
        $context->setMessage($message);
        $context->setLogger(new NullLogger());

        self::assertNull($context->getStatus());

        $extension = new DelayRedeliveredMessageExtension($driver, 12345);
        $extension->onPreReceived($context);

        self::assertEquals(MessageProcessorInterface::REJECT, $context->getStatus());
        self::assertArrayNotHasKey(
            DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT,
            $message->getProperties()
        );
    }

    public function testShouldIncrementRedeliverCountHeaderAndSetOriginalCountAfterDelay()
    {
        $queue = new NullQueue('queue');

        $message = new NullMessage();
        $message->setRedelivered(true);
        $message->setProperties([
            DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT => 7,
        ]);

        $driver = $this->createDriverMock();
        $driver
            ->expects(self::once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->isInstanceOf(Message::class))
            ->will($this->returnCallback(function (QueueInterface $queue, Message $message) {
                $properties = $message->getProperties();
                self::assertArrayHasKey(DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT, $properties);
                self::assertSame(8, $properties[DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT]);
            }))
        ;

        $session = $this->createSessionMock();
        $session
            ->expects(self::once())
            ->method('createQueue')
            ->with('queue')
            ->will($this->returnValue($queue))
        ;

        $context = new Context($session);
        $context->setQueueName('queue');
        $context->setMessage($message);
        $context->setLogger(new NullLogger());

        self::assertNull($context->getStatus());

        $extension = new DelayRedeliveredMessageExtension($driver, 12345);
        $extension->onPreReceived($context);

        self::assertEquals(MessageProcessorInterface::REJECT, $context->getStatus());
        $properties = $message->getProperties();
        self::assertArrayHasKey(DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT, $properties);
        self::assertSame(7, $properties[DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT]);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    private function createDriverMock()
    {
        return $this->getMock(DriverInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|SessionInterface
     */
    private function createSessionMock()
    {
        return $this->getMock(SessionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->getMock(LoggerInterface::class);
    }
}
