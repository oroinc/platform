<?php
namespace Oro\Component\Messaging\Tests\Consumption;

use Oro\Component\Messaging\Consumption\Context;
use Oro\Component\Messaging\Consumption\Extension;
use Oro\Component\Messaging\Consumption\Extensions;
use Oro\Component\Messaging\Consumption\ExtensionTrait;
use Oro\Component\Messaging\Consumption\MessageProcessor;
use Oro\Component\Messaging\Consumption\QueueConsumer;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\MessageConsumer;
use Oro\Component\Messaging\Transport\Queue;
use Oro\Component\Messaging\Transport\Session;
use Psr\Log\NullLogger;
use Symfony\Component\Validator\Constraints\Null;

// @codingStandardsIgnoreStart

class QueueConsumerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithSessionAndExtensionsAsArguments()
    {
        new QueueConsumer($this->createSessionStub(), new Extensions([]));
    }

    public function testShouldSubscribeToGivenQueueAndQuitAfterFifthIdleCycle()
    {
        $expectedQueueName = 'theQueueName';
        $expectedQueue = $this->getMock(Queue::class);

        $messageConsumerMock = $this->getMock(MessageConsumer::class);
        $messageConsumerMock
            ->expects($this->exactly(5))
            ->method('receive')
            ->willReturn(null)
        ;

        $sessionMock = $this->getMock(Session::class);
        $sessionMock
            ->expects($this->once())
            ->method('createConsumer')
            ->with($this->identicalTo($expectedQueue))
            ->willReturn($messageConsumerMock)
        ;
        $sessionMock
            ->expects($this->once())
            ->method('createQueue')
            ->with($expectedQueueName)
            ->willReturn($expectedQueue)
        ;

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->never())
            ->method('process')
        ;

        $queueConsumer = new QueueConsumer($sessionMock, new Extensions([new BreakCycleExtension(5)]));

        $queueConsumer->consume($expectedQueueName, $messageProcessorMock);
    }

    public function testShouldProcessFiveMessagesAndQuit()
    {
        $messageMock = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($messageMock);

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->exactly(5))
            ->method('process')
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([new BreakCycleExtension(5)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldAckMessageIfMessageProcessorReturnSuchStatus()
    {
        $messageMock = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($messageMock);
        $messageConsumerStub
            ->expects($this->once())
            ->method('acknowledge')
            ->with($this->identicalTo($messageMock))
        ;

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn(MessageProcessor::ACK)
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldAckMessageIfMessageProcessorReturnNull()
    {
        $messageMock = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($messageMock);
        $messageConsumerStub
            ->expects($this->once())
            ->method('acknowledge')
            ->with($this->identicalTo($messageMock))
        ;

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn(null)
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldRejectMessageIfMessageProcessorReturnSuchStatus()
    {
        $messageMock = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($messageMock);
        $messageConsumerStub
            ->expects($this->once())
            ->method('reject')
            ->with($this->identicalTo($messageMock), false)
        ;

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn(MessageProcessor::REJECT)
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldRequeueMessageIfMessageProcessorReturnSuchStatus()
    {
        $messageMock = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($messageMock);
        $messageConsumerStub
            ->expects($this->once())
            ->method('reject')
            ->with($this->identicalTo($messageMock), true)
        ;

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn(MessageProcessor::REQUEUE)
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Processor returned not supported status: invalidStatus
     */
    public function testThrowIfMessageProcessorReturnInvalidStatus()
    {
        $messageMock = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($messageMock);

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->once())
            ->method('process')
            ->with($this->identicalTo($messageMock))
            ->willReturn('invalidStatus')
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldNotPassMessageToMessageProcessorIfItWasProcessedByExtension()
    {
        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onPreReceived')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setStatus(MessageProcessor::ACK);
            })
        ;

        $messageMock = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($messageMock);

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->never())
            ->method('process')
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([$extension, new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldCallOnStartExtensionMethod()
    {
        $messageConsumerStub = $this->createMessageConsumerStub($message = null);

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($sessionStub, $messageConsumerStub, $messageProcessorMock) {
                $this->assertSame($sessionStub, $context->getSession());
                $this->assertSame($messageConsumerStub, $context->getMessageConsumer());
                $this->assertSame($messageProcessorMock, $context->getMessageProcessor());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getStatus());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([$extension, new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldCallOnIdleExtensionMethod()
    {
        $messageConsumerStub = $this->createMessageConsumerStub($message = null);

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onIdle')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($sessionStub, $messageConsumerStub, $messageProcessorMock) {
                $this->assertSame($sessionStub, $context->getSession());
                $this->assertSame($messageConsumerStub, $context->getMessageConsumer());
                $this->assertSame($messageProcessorMock, $context->getMessageProcessor());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getStatus());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([$extension, new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldCallOnBeforeReceiveExtensionMethod()
    {
        $expectedMessage = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($expectedMessage);

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onBeforeReceive')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($sessionStub, $messageConsumerStub, $messageProcessorMock, $expectedMessage) {
                $this->assertSame($sessionStub, $context->getSession());
                $this->assertSame($messageConsumerStub, $context->getMessageConsumer());
                $this->assertSame($messageProcessorMock, $context->getMessageProcessor());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getStatus());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([$extension, new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldCallOnPreReceivedAndPostReceivedExtensionMethods()
    {
        $expectedMessage = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($expectedMessage);

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onPreReceived')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($sessionStub, $messageConsumerStub, $messageProcessorMock, $expectedMessage) {
                $this->assertSame($sessionStub, $context->getSession());
                $this->assertSame($messageConsumerStub, $context->getMessageConsumer());
                $this->assertSame($messageProcessorMock, $context->getMessageProcessor());
                $this->assertSame($expectedMessage, $context->getMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertNull($context->getStatus());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;
        $extension
            ->expects($this->once())
            ->method('onPostReceived')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($sessionStub, $messageConsumerStub, $messageProcessorMock, $expectedMessage) {
                $this->assertSame($sessionStub, $context->getSession());
                $this->assertSame($messageConsumerStub, $context->getMessageConsumer());
                $this->assertSame($messageProcessorMock, $context->getMessageProcessor());
                $this->assertSame($expectedMessage, $context->getMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(MessageProcessor::ACK, $context->getStatus());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([$extension, new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldAllowInterruptConsumingOnIdle()
    {
        $messageConsumerStub = $this->createMessageConsumerStub($message = null);

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onIdle')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setExecutionInterrupted(true);
            })
        ;
        $extension
            ->expects($this->once())
            ->method('onInterrupted')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($sessionStub, $messageConsumerStub, $messageProcessorMock) {
                $this->assertSame($sessionStub, $context->getSession());
                $this->assertSame($messageConsumerStub, $context->getMessageConsumer());
                $this->assertSame($messageProcessorMock, $context->getMessageProcessor());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getStatus());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([$extension, new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldAllowInterruptConsumingOnPreReceiveButProcessCurrentMessage()
    {
        $expectedMessage = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($expectedMessage);

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->once())
            ->method('process')
        ;

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onPreReceived')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setExecutionInterrupted(true);
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onInterrupted')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($sessionStub, $messageConsumerStub, $messageProcessorMock, $expectedMessage) {
                $this->assertSame($sessionStub, $context->getSession());
                $this->assertSame($messageConsumerStub, $context->getMessageConsumer());
                $this->assertSame($messageProcessorMock, $context->getMessageProcessor());
                $this->assertSame($expectedMessage, $context->getMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(MessageProcessor::ACK, $context->getStatus());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([$extension, new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldAllowInterruptConsumingOnPostReceive()
    {
        $expectedMessage = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($expectedMessage);

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->once())
            ->method('process')
        ;

        $extension = $this->createExtension();
        $extension
            ->expects($this->once())
            ->method('onPostReceived')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setExecutionInterrupted(true);
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onInterrupted')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($sessionStub, $messageConsumerStub, $messageProcessorMock, $expectedMessage) {
                $this->assertSame($sessionStub, $context->getSession());
                $this->assertSame($messageConsumerStub, $context->getMessageConsumer());
                $this->assertSame($messageProcessorMock, $context->getMessageProcessor());
                $this->assertSame($expectedMessage, $context->getMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(MessageProcessor::ACK, $context->getStatus());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([$extension, new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Process failed
     */
    public function testShouldCallOnInterruptedIfExceptionThrow()
    {
        $expectedException = new \Exception('Process failed');
        $expectedMessage = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($expectedMessage);

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->once())
            ->method('process')
            ->willThrowException($expectedException)
        ;

        $extension = $this->createExtension();
        $extension
            ->expects($this->atLeastOnce())
            ->method('onInterrupted')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($sessionStub, $messageConsumerStub, $messageProcessorMock, $expectedMessage, $expectedException) {
                $this->assertSame($sessionStub, $context->getSession());
                $this->assertSame($messageConsumerStub, $context->getMessageConsumer());
                $this->assertSame($messageProcessorMock, $context->getMessageProcessor());
                $this->assertSame($expectedMessage, $context->getMessage());
                $this->assertSame($expectedException, $context->getException());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getStatus());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([$extension, new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldCallExtensionPassedOnRuntime()
    {
        $expectedMessage = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($expectedMessage);

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->once())
            ->method('process')
        ;

        $runtimeExtension = $this->createExtension();
        $runtimeExtension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->isInstanceOf(Context::class))
        ;
        $runtimeExtension
            ->expects($this->once())
            ->method('onBeforeReceive')
            ->with($this->isInstanceOf(Context::class))
        ;
        $runtimeExtension
            ->expects($this->once())
            ->method('onPreReceived')
            ->with($this->isInstanceOf(Context::class))
        ;
        $runtimeExtension
            ->expects($this->once())
            ->method('onPostReceived')
            ->with($this->isInstanceOf(Context::class))
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock, new Extensions([$runtimeExtension]));
    }

    public function testShouldChangeLoggerOnStart()
    {
        $expectedMessage = $this->createMessageMock();
        $messageConsumerStub = $this->createMessageConsumerStub($expectedMessage);

        $sessionStub = $this->createSessionStub($messageConsumerStub);

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->once())
            ->method('process')
        ;

        $expectedLogger = new NullLogger();

        $extension = $this->createExtension();
        $extension
            ->expects($this->atLeastOnce())
            ->method('onStart')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($expectedLogger) {
                $context->setLogger($expectedLogger);
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onBeforeReceive')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($expectedLogger) {
                $this->assertSame($expectedLogger, $context->getLogger());
            })
        ;
        $extension
            ->expects($this->atLeastOnce())
            ->method('onPreReceived')
            ->with($this->isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($expectedLogger) {
                $this->assertSame($expectedLogger, $context->getLogger());
            })
        ;

        $queueConsumer = new QueueConsumer($sessionStub, new Extensions([$extension, new BreakCycleExtension(1)]));

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageConsumer
     */
    protected function createMessageConsumerStub($message = null)
    {
        $messageConsumerMock = $this->getMock(MessageConsumer::class);
        $messageConsumerMock
            ->expects($this->any())
            ->method('receive')
            ->willReturn($message)
        ;

        return $messageConsumerMock;
    }

    /**
     * @return Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createSessionStub($messageConsumer = null)
    {
        $sessionMock = $this->getMock(Session::class);
        $sessionMock
            ->expects($this->any())
            ->method('createConsumer')
            ->willReturn($messageConsumer)
        ;
        $sessionMock
            ->expects($this->any())
            ->method('createQueue')
            ->willReturn($this->getMock(Queue::class))
        ;

        return $sessionMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProcessor
     */
    protected function createMessageProcessorMock()
    {
        return $this->getMock(MessageProcessor::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Message
     */
    protected function createMessageMock()
    {
        return $this->getMock(Message::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Extension
     */
    protected function createExtension()
    {
        return $this->getMock(Extension::class);
    }
}

class BreakCycleExtension implements Extension
{
    use ExtensionTrait;

    protected $cycles = 1;

    private $limit;

    public function __construct($limit)
    {
        $this->limit = $limit;
    }

    public function onPostReceived(Context $context)
    {
        $this->onIdle($context);
    }

    public function onIdle(Context $context)
    {
        if ($this->cycles >= $this->limit) {
            $context->setExecutionInterrupted(true);
        } else {
            $this->cycles++;
        }
    }
}

// @codingStandardsIgnoreEnd