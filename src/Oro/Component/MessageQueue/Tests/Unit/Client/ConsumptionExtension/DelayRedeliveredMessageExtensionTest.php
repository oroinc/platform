<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Client\ConsumptionExtension\DelayRedeliveredMessageExtension;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
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

    public function testShouldDelayMessageAndRejectOriginalMessage()
    {
        $queue = new NullQueue('queue');

        $message = new NullMessage();
        $message->setRedelivered(true);

        $driver = $this->createDriverMock();
        $driver
            ->expects($this->once())
            ->method('delayMessage')
            ->with($this->identicalTo($queue), $this->identicalTo($message), 12345)
        ;

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
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
            ->with('[DelayRedeliveredMessageExtension] Set reject message status to context')
        ;

        $context = new Context($session);
        $context->setQueueName('queue');
        $context->setMessage($message);
        $context->setLogger($logger);

        $this->assertNull($context->getStatus());

        $extension = new DelayRedeliveredMessageExtension($driver, 12345);
        $extension->onPreReceived($context);

        $this->assertEquals(MessageProcessorInterface::REJECT, $context->getStatus());
    }

    public function testShouldDoNothingIfMessageIsNotRedelivered()
    {
        $message = new NullMessage();

        $driver = $this->createDriverMock();
        $driver
            ->expects($this->never())
            ->method('delayMessage')
        ;

        $context = new Context($this->createSessionMock());
        $context->setQueueName('queue');
        $context->setMessage($message);

        $extension = new DelayRedeliveredMessageExtension($driver, 12345);
        $extension->onPreReceived($context);

        $this->assertNull($context->getStatus());
    }

    public function testShouldAddRedeliverCountHeaderAndRemoveItAfterDelayFromOriginalMessage()
    {
        $queue = new NullQueue('queue');

        $message = new NullMessage();
        $message->setRedelivered(true);

        $driver = $this->createDriverMock();
        $driver
            ->expects($this->once())
            ->method('delayMessage')
            ->with($this->identicalTo($queue), $this->identicalTo($message), 12345)
            ->will($this->returnCallback(function (QueueInterface $queue, MessageInterface $message, $delay) {
                $properties = $message->getProperties();
                $this->assertArrayHasKey(DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT, $properties);
                $this->assertSame(1, $properties[DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT]);
            }))
        ;

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('createQueue')
            ->with('queue')
            ->will($this->returnValue($queue))
        ;

        $context = new Context($session);
        $context->setQueueName('queue');
        $context->setMessage($message);
        $context->setLogger(new NullLogger());

        $this->assertNull($context->getStatus());

        $extension = new DelayRedeliveredMessageExtension($driver, 12345);
        $extension->onPreReceived($context);

        $this->assertEquals(MessageProcessorInterface::REJECT, $context->getStatus());
        $this->assertArrayNotHasKey(
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
            ->expects($this->once())
            ->method('delayMessage')
            ->with($this->identicalTo($queue), $this->identicalTo($message), 12345)
            ->will($this->returnCallback(function (QueueInterface $queue, MessageInterface $message, $delay) {
                $properties = $message->getProperties();
                $this->assertArrayHasKey(DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT, $properties);
                $this->assertSame(8, $properties[DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT]);
            }))
        ;

        $session = $this->createSessionMock();
        $session
            ->expects($this->once())
            ->method('createQueue')
            ->with('queue')
            ->will($this->returnValue($queue))
        ;

        $context = new Context($session);
        $context->setQueueName('queue');
        $context->setMessage($message);
        $context->setLogger(new NullLogger());

        $this->assertNull($context->getStatus());

        $extension = new DelayRedeliveredMessageExtension($driver, 12345);
        $extension->onPreReceived($context);

        $this->assertEquals(MessageProcessorInterface::REJECT, $context->getStatus());
        $properties = $message->getProperties();
        $this->assertArrayHasKey(DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT, $properties);
        $this->assertSame(7, $properties[DelayRedeliveredMessageExtension::PROPERTY_REDELIVER_COUNT]);
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
