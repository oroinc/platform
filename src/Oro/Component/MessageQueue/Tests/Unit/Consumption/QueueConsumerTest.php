<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\Extensions;
use Oro\Component\MessageQueue\Consumption\ExtensionTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\NullLogger;

// @codingStandardsIgnoreStart

class QueueConsumerTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithConnectionAndExtensionsAsArguments()
    {
        new QueueConsumer($this->createConnectionStub(), new Extensions([]), 0);
    }

    public function testShouldAllowGetConnectionSetInConstructor()
    {
        $expectedConnection = $this->createConnectionStub();

        $consumer = new QueueConsumer($expectedConnection, new Extensions([]), 0);

        $this->assertSame($expectedConnection, $consumer->getConnection());
    }

    public function testShouldSubscribeToGivenQueueAndQuitAfterFifthIdleCycle()
    {
        $expectedQueueName = 'theQueueName';
        $expectedQueue = $this->getMock(QueueInterface::class);

        $messageConsumerMock = $this->getMock(MessageConsumerInterface::class);
        $messageConsumerMock
            ->expects($this->exactly(5))
            ->method('receive')
            ->willReturn(null)
        ;

        $sessionMock = $this->getMock(SessionInterface::class);
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

        $connectionStub = $this->createConnectionStub($sessionMock);

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->never())
            ->method('process')
        ;

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([new BreakCycleExtension(5)]), 0);

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

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([new BreakCycleExtension(5)]), 0);

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
            ->willReturn(MessageProcessorInterface::ACK)
        ;

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([new BreakCycleExtension(1)]), 0);

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

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([new BreakCycleExtension(1)]), 0);

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
            ->willReturn(MessageProcessorInterface::REJECT)
        ;

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([new BreakCycleExtension(1)]), 0);

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
            ->willReturn(MessageProcessorInterface::REQUEUE)
        ;

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([new BreakCycleExtension(1)]), 0);

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testThrowIfMessageProcessorReturnInvalidStatus()
    {
        $this->setExpectedException(\LogicException::class, 'Status is not supported: invalidStatus');
        
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

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([new BreakCycleExtension(1)]), 0);

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
                $context->setStatus(MessageProcessorInterface::ACK);
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

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([$extension, new BreakCycleExtension(1)]), 0);

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

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([$extension, new BreakCycleExtension(1)]), 0);

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

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([$extension, new BreakCycleExtension(1)]), 0);

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

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([$extension, new BreakCycleExtension(1)]), 0);

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
                $this->assertSame(MessageProcessorInterface::ACK, $context->getStatus());
                $this->assertFalse($context->isExecutionInterrupted());
            })
        ;

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([$extension, new BreakCycleExtension(1)]), 0);

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

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([$extension, new BreakCycleExtension(1)]), 0);

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldCloseSessionWhenConsumptionInterrupted()
    {
        $messageConsumerStub = $this->createMessageConsumerStub($message = null);

        $sessionMock = $this->createSessionStub($messageConsumerStub);
        $sessionMock
            ->expects($this->once())
            ->method('close')
        ;

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

        $connectionStub = $this->createConnectionStub($sessionMock);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([$extension, new BreakCycleExtension(1)]), 0);

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldCloseSessionWhenConsumptionInterruptedByException()
    {
        $messageConsumerStub = $this->createMessageConsumerStub($message = $this->createMessageMock());

        $sessionMock = $this->createSessionStub($messageConsumerStub);
        $sessionMock
            ->expects($this->once())
            ->method('close')
        ;

        $messageProcessorMock = $this->createMessageProcessorMock();
        $messageProcessorMock
            ->expects($this->once())
            ->method('process')
            ->willThrowException(new \Exception)
        ;

        $connectionStub = $this->createConnectionStub($sessionMock);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([new BreakCycleExtension(1)]), 0);

        $this->setExpectedException(\Exception::class);
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
                $this->assertSame(MessageProcessorInterface::ACK, $context->getStatus());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([$extension, new BreakCycleExtension(1)]), 0);

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
                $this->assertSame(MessageProcessorInterface::ACK, $context->getStatus());
                $this->assertTrue($context->isExecutionInterrupted());
            })
        ;

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([$extension, new BreakCycleExtension(1)]), 0);

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    public function testShouldCallOnInterruptedIfExceptionThrow()
    {
        $this->setExpectedException(\Exception::class, 'Process failed');

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

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([$extension, new BreakCycleExtension(1)]), 0);

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

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([new BreakCycleExtension(1)]), 0);

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

        $connectionStub = $this->createConnectionStub($sessionStub);

        $queueConsumer = new QueueConsumer($connectionStub, new Extensions([$extension, new BreakCycleExtension(1)]), 0);

        $queueConsumer->consume('aQueueName', $messageProcessorMock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageConsumerInterface
     */
    protected function createMessageConsumerStub($message = null)
    {
        $messageConsumerMock = $this->getMock(MessageConsumerInterface::class);
        $messageConsumerMock
            ->expects($this->any())
            ->method('receive')
            ->willReturn($message)
        ;

        return $messageConsumerMock;
    }

    /**
     * @return ConnectionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createConnectionStub($session = null)
    {
        $connectionMock = $this->getMock(ConnectionInterface::class);
        $connectionMock
            ->expects($this->any())
            ->method('createSession')
            ->willReturn($session)
        ;

        return $connectionMock;
    }

    /**
     * @return SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createSessionStub($messageConsumer = null)
    {
        $sessionMock = $this->getMock(SessionInterface::class);
        $sessionMock
            ->expects($this->any())
            ->method('createConsumer')
            ->willReturn($messageConsumer)
        ;
        $sessionMock
            ->expects($this->any())
            ->method('createQueue')
            ->willReturn($this->getMock(QueueInterface::class))
        ;
        $sessionMock
            ->expects($this->any())
            ->method('close')
        ;

        return $sessionMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProcessorInterface
     */
    protected function createMessageProcessorMock()
    {
        return $this->getMock(MessageProcessorInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageInterface
     */
    protected function createMessageMock()
    {
        return $this->getMock(MessageInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExtensionInterface
     */
    protected function createExtension()
    {
        return $this->getMock(ExtensionInterface::class);
    }
}

class BreakCycleExtension implements ExtensionInterface
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