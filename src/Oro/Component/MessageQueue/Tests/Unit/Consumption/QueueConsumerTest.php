<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Client\MessageProcessorRegistryInterface;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Consumption\ExtensionInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Exception\StaleJobRuntimeException;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Oro\Component\MessageQueue\Tests\Unit\Consumption\Mock\BreakCycleExtension;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\Queue;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Psr\Log\NullLogger;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QueueConsumerTest extends \PHPUnit\Framework\TestCase
{
    private const MESSAGE_PROCESSOR_NAME = 'sample_processor';
    private const QUEUE_NAME = 'sample_queue';

    private SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session;

    private ConnectionInterface|\PHPUnit\Framework\MockObject\MockObject $connection;

    private ConsumerState|\PHPUnit\Framework\MockObject\MockObject $consumerState;

    private MessageProcessorRegistryInterface|\PHPUnit\Framework\MockObject\MockObject $messageProcessorRegistry;

    private MessageProcessorInterface|\PHPUnit\Framework\MockObject\MockObject $messageProcessor;

    private MessageConsumerInterface|\PHPUnit\Framework\MockObject\MockObject $messageConsumer;

    private Message $message;

    protected function setUp(): void
    {
        $this->messageConsumer = $this->createMock(MessageConsumerInterface::class);
        $this->session = $this->createSession();
        $this->session->expects(self::any())
            ->method('createConsumer')
            ->willReturn($this->messageConsumer);

        $this->connection = $this->createConnection($this->session);
        $this->consumerState = $this->createMock(ConsumerState::class);
        $this->messageProcessorRegistry = $this->createMock(MessageProcessorRegistryInterface::class);
        $this->messageProcessor = $this->createMock(MessageProcessorInterface::class);
        $this->messageProcessorRegistry
            ->expects(self::any())
            ->method('get')
            ->willReturnMap(
                [
                    [self::MESSAGE_PROCESSOR_NAME, $this->messageProcessor],
                ]
            );

        $this->message = new Message();
    }

    public function testShouldAllowGetConnectionSetInConstructor(): void
    {
        self::assertSame($this->connection, $this->createQueueConsumer()->getConnection());
    }

    public function testThrowIfQueueNameEmptyOnBind(): void
    {
        $this->expectExceptionObject(new \LogicException('The queue name must be not empty.'));

        $this->createQueueConsumer()->bind('');
    }

    public function testThrowIfQueueAlreadyBoundToMessageProcessorOnBind(): void
    {
        $consumer = $this->createQueueConsumer();
        $consumer->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME);

        $this->expectExceptionObject(
            new \LogicException(sprintf('The queue was already bound. Queue: %s', self::QUEUE_NAME))
        );

        $consumer->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME);
    }

    public function testShouldReturnSelfOnBind(): void
    {
        $consumer = $this->createQueueConsumer();
        self::assertSame($consumer, $consumer->bind(self::QUEUE_NAME));
    }

    public function testShouldSubscribeToGivenQueueAndQuitAfterFifthIdleCycle(): void
    {
        $this->messageConsumer
            ->expects(self::exactly(5))
            ->method('receive')
            ->willReturn(null);

        $this->messageProcessor
            ->expects(self::never())
            ->method('process');

        $this
            ->createQueueConsumer(null, new BreakCycleExtension(5))
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldProcessFiveMessagesAndQuit(): void
    {
        $this->messageConsumer
            ->expects(self::exactly(5))
            ->method('receive')
            ->willReturn($this->message);

        $this->messageProcessor
            ->expects(self::exactly(5))
            ->method('process')
            ->willReturn(MessageProcessorInterface::ACK);

        $this
            ->createQueueConsumer(null, new BreakCycleExtension(5))
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testThrowIfProcessorThrowsStaleException(): void
    {
        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $this->messageProcessor
            ->expects(self::once())
            ->method('process')
            ->willThrowException(StaleJobRuntimeException::create());

        $this->expectException(StaleJobRuntimeException::class);
        $this->expectExceptionMessage('Stale Jobs cannot be run');

        $this
            ->createQueueConsumer()
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldAckMessageIfMessageProcessorReturnSuchStatus(): void
    {
        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $this->messageConsumer
            ->expects(self::once())
            ->method('acknowledge')
            ->with(self::identicalTo($this->message));

        $this->messageProcessor
            ->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($this->message))
            ->willReturn(MessageProcessorInterface::ACK);

        $this
            ->createQueueConsumer()
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testThrowIfMessageProcessorReturnNull(): void
    {
        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $this->messageProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($this->message))
            ->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Status is not supported');

        $this
            ->createQueueConsumer()
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldRejectMessageIfMessageProcessorReturnSuchStatus(): void
    {
        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $this->messageConsumer->expects(self::once())
            ->method('reject')
            ->with(self::identicalTo($this->message), false);

        $this->messageProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($this->message))
            ->willReturn(MessageProcessorInterface::REJECT);

        $this
            ->createQueueConsumer()
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldRequeueMessageIfMessageProcessorReturnSuchStatus(): void
    {
        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $this->messageConsumer->expects(self::once())
            ->method('reject')
            ->with(self::identicalTo($this->message), true);

        $this->messageProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($this->message))
            ->willReturn(MessageProcessorInterface::REQUEUE);

        $this
            ->createQueueConsumer()
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testThrowIfMessageProcessorReturnInvalidStatus(): void
    {
        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $this->messageProcessor->expects(self::once())
            ->method('process')
            ->with(self::identicalTo($this->message))
            ->willReturn('invalidStatus');

        $this->expectExceptionObject(new \LogicException('Status is not supported: invalidStatus'));

        $this
            ->createQueueConsumer()
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldNotPassMessageToMessageProcessorIfItWasProcessedByExtension(): void
    {
        $extension = $this->createExtension();
        $extension->expects(self::once())
            ->method('onPreReceived')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setStatus(MessageProcessorInterface::ACK);
            });

        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $this->messageProcessor
            ->expects(self::never())
            ->method('process');

        $this
            ->createQueueConsumer(null, new ChainExtension([$extension, new BreakCycleExtension(1)]))
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldCallOnStartExtensionMethod(): void
    {
        $extension = $this->createExtension();
        $extension->expects(self::once())
            ->method('onStart')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $this->assertSame($this->session, $context->getSession());
                $this->assertNull($context->getMessageConsumer());
                $this->assertEmpty($context->getMessageProcessorName());
                $this->assertNull($context->getLogger());
                $this->assertNull($context->getMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getStatus());
                $this->assertNull($context->getQueueName());
                $this->assertFalse($context->isExecutionInterrupted());
            });

        $this
            ->createQueueConsumer(null, new ChainExtension([$extension, new BreakCycleExtension(1)]))
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldCallOnIdleExtensionMethod(): void
    {
        $extension = $this->createExtension();
        $extension->expects(self::once())
            ->method('onIdle')
            ->willReturnCallback(function (Context $context) {
                $this->assertSame($this->session, $context->getSession());
                $this->assertSame($this->messageConsumer, $context->getMessageConsumer());
                $this->assertEquals(self::MESSAGE_PROCESSOR_NAME, $context->getMessageProcessorName());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getStatus());
                $this->assertFalse($context->isExecutionInterrupted());
            });

        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn(null);

        $this
            ->createQueueConsumer(null, new ChainExtension([$extension, new BreakCycleExtension(1)]))
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldCallOnBeforeReceiveExtensionMethod(): void
    {
        $extension = $this->createExtension();
        $extension->expects(self::once())
            ->method('onBeforeReceive')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $this->assertSame($this->session, $context->getSession());
                $this->assertSame($this->messageConsumer, $context->getMessageConsumer());
                $this->assertEquals(self::MESSAGE_PROCESSOR_NAME, $context->getMessageProcessorName());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getStatus());
                $this->assertFalse($context->isExecutionInterrupted());
                $this->assertEquals(self::QUEUE_NAME, $context->getQueueName());
            });

        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $this->messageProcessor
            ->expects(self::any())
            ->method('process')
            ->willReturn(MessageProcessorInterface::ACK);

        $this
            ->createQueueConsumer(null, new ChainExtension([$extension, new BreakCycleExtension(1)]))
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldCallOnPreReceivedAndPostReceivedExtensionMethods(): void
    {
        $extension = $this->createExtension();
        $extension->expects(self::once())
            ->method('onPreReceived')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $this->assertSame($this->session, $context->getSession());
                $this->assertSame($this->messageConsumer, $context->getMessageConsumer());
                $this->assertEquals(self::MESSAGE_PROCESSOR_NAME, $context->getMessageProcessorName());
                $this->assertSame($this->message, $context->getMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertNull($context->getStatus());
                $this->assertFalse($context->isExecutionInterrupted());
            });
        $extension->expects(self::once())
            ->method('onPostReceived')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $this->assertSame($this->session, $context->getSession());
                $this->assertSame($this->messageConsumer, $context->getMessageConsumer());
                $this->assertEquals(self::MESSAGE_PROCESSOR_NAME, $context->getMessageProcessorName());
                $this->assertSame($this->message, $context->getMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(MessageProcessorInterface::ACK, $context->getStatus());
                $this->assertFalse($context->isExecutionInterrupted());
            });

        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $this->messageProcessor
            ->expects(self::any())
            ->method('process')
            ->willReturn(MessageProcessorInterface::ACK);

        $this
            ->createQueueConsumer(null, new ChainExtension([$extension, new BreakCycleExtension(1)]))
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldAllowInterruptConsumingOnIdle(): void
    {
        $extension = $this->createExtension();
        $extension->expects(self::once())
            ->method('onIdle')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setExecutionInterrupted(true);
            });
        $extension->expects(self::once())
            ->method('onInterrupted')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $this->assertSame($this->session, $context->getSession());
                $this->assertSame($this->messageConsumer, $context->getMessageConsumer());
                $this->assertEquals(self::MESSAGE_PROCESSOR_NAME, $context->getMessageProcessorName());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getMessage());
                $this->assertNull($context->getException());
                $this->assertNull($context->getStatus());
                $this->assertTrue($context->isExecutionInterrupted());
            });

        $this
            ->createQueueConsumer(null, new ChainExtension([$extension, new BreakCycleExtension(1)]))
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldCloseSessionWhenConsumptionInterrupted(): void
    {
        $extension = $this->createExtension();
        $extension
            ->expects(self::once())
            ->method('onIdle')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setExecutionInterrupted(true);
            });

        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn(null);

        $session = $this->createSession();
        $connection = $this->createConnection($session);
        $session
            ->expects(self::any())
            ->method('createConsumer')
            ->willReturn($this->messageConsumer);
        $session
            ->expects(self::once())
            ->method('close');

        $this
            ->createQueueConsumer($connection, new ChainExtension([$extension, new BreakCycleExtension(1)]))
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldCloseSessionWhenConsumptionInterruptedByException(): void
    {
        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $expectedException = new \Exception();
        $this->messageProcessor
            ->expects(self::once())
            ->method('process')
            ->willThrowException($expectedException);

        try {
            $this
                ->createQueueConsumer()
                ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
                ->consume();
        } catch (\Exception $e) {
            self::assertSame($expectedException, $e);
            self::assertNull($e->getPrevious());

            return;
        }

        self::fail('Exception throw is expected.');
    }

    public function testShouldSetMainExceptionAsPreviousToExceptionThrownOnInterrupt(): void
    {
        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $mainException = new \Exception();
        $expectedException = new \Exception();

        $this->messageProcessor
            ->expects(self::once())
            ->method('process')
            ->willThrowException($mainException);

        $extension = $this->createExtension();
        $extension
            ->expects(self::atLeastOnce())
            ->method('onInterrupted')
            ->willThrowException($expectedException);

        try {
            $this
                ->createQueueConsumer(null, new ChainExtension([$extension, new BreakCycleExtension(1)]))
                ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
                ->consume();
        } catch (\Exception $e) {
            self::assertSame($expectedException, $e);
            self::assertSame($mainException, $e->getPrevious());

            return;
        }

        self::fail('Exception throw is expected.');
    }

    public function testShouldAllowInterruptConsumingOnPreReceiveButProcessCurrentMessage(): void
    {
        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $this->messageProcessor
            ->expects(self::once())
            ->method('process')
            ->willReturn(MessageProcessorInterface::ACK);

        $extension = $this->createExtension();
        $extension->expects(self::once())
            ->method('onPreReceived')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setExecutionInterrupted(true);
            });
        $extension->expects(self::atLeastOnce())
            ->method('onInterrupted')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $this->assertSame($this->session, $context->getSession());
                $this->assertSame($this->messageConsumer, $context->getMessageConsumer());
                $this->assertEquals(self::MESSAGE_PROCESSOR_NAME, $context->getMessageProcessorName());
                $this->assertSame($this->message, $context->getMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(MessageProcessorInterface::ACK, $context->getStatus());
                $this->assertTrue($context->isExecutionInterrupted());
            });


        $this
            ->createQueueConsumer(null, new ChainExtension([$extension, new BreakCycleExtension(1)]))
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldAllowInterruptConsumingOnPostReceive(): void
    {
        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $this->messageProcessor
            ->expects(self::once())
            ->method('process')
            ->willReturn(MessageProcessorInterface::ACK);

        $extension = $this->createExtension();
        $extension->expects(self::once())
            ->method('onPostReceived')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $context->setExecutionInterrupted(true);
            });
        $extension->expects(self::atLeastOnce())
            ->method('onInterrupted')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) {
                $this->assertSame($this->session, $context->getSession());
                $this->assertSame($this->messageConsumer, $context->getMessageConsumer());
                $this->assertEquals(self::MESSAGE_PROCESSOR_NAME, $context->getMessageProcessorName());
                $this->assertSame($this->message, $context->getMessage());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getException());
                $this->assertSame(MessageProcessorInterface::ACK, $context->getStatus());
                $this->assertTrue($context->isExecutionInterrupted());
            });


        $this
            ->createQueueConsumer(null, new ChainExtension([$extension, new BreakCycleExtension(1)]))
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldCallOnInterruptedIfExceptionThrow(): void
    {
        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $expectedException = new \Exception('Process failed');
        $this->messageProcessor
            ->expects(self::once())
            ->method('process')
            ->willThrowException($expectedException);

        $extension = $this->createExtension();
        $extension->expects(self::atLeastOnce())
            ->method('onInterrupted')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($expectedException) {
                $this->assertSame($this->session, $context->getSession());
                $this->assertSame($this->messageConsumer, $context->getMessageConsumer());
                $this->assertEquals(self::MESSAGE_PROCESSOR_NAME, $context->getMessageProcessorName());
                $this->assertSame($this->message, $context->getMessage());
                $this->assertSame($expectedException, $context->getException());
                $this->assertInstanceOf(NullLogger::class, $context->getLogger());
                $this->assertNull($context->getStatus());
                $this->assertTrue($context->isExecutionInterrupted());
            });

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Process failed');

        $this
            ->createQueueConsumer(null, new ChainExtension([$extension, new BreakCycleExtension(1)]))
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldCallExtensionPassedOnRuntime(): void
    {
        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $this->messageProcessor
            ->expects(self::once())
            ->method('process')
            ->willReturn(MessageProcessorInterface::ACK);

        $runtimeExtension = $this->createExtension();
        $runtimeExtension->expects(self::once())
            ->method('onStart')
            ->with(self::isInstanceOf(Context::class));
        $runtimeExtension->expects(self::once())
            ->method('onBeforeReceive')
            ->with(self::isInstanceOf(Context::class));
        $runtimeExtension->expects(self::once())
            ->method('onPreReceived')
            ->with(self::isInstanceOf(Context::class));
        $runtimeExtension->expects(self::once())
            ->method('onPostReceived')
            ->with(self::isInstanceOf(Context::class));

        $this
            ->createQueueConsumer()
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume(new ChainExtension([$runtimeExtension]));
    }

    public function testShouldChangeLoggerOnStart(): void
    {
        $this->messageConsumer
            ->expects(self::once())
            ->method('receive')
            ->willReturn($this->message);

        $this->messageProcessor
            ->expects(self::once())
            ->method('process')
            ->willReturn(MessageProcessorInterface::ACK);

        $expectedLogger = new NullLogger();

        $extension = $this->createExtension();
        $extension->expects(self::atLeastOnce())
            ->method('onStart')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($expectedLogger) {
                $context->setLogger($expectedLogger);
            });
        $extension->expects(self::atLeastOnce())
            ->method('onBeforeReceive')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($expectedLogger) {
                $this->assertSame($expectedLogger, $context->getLogger());
            });
        $extension->expects(self::atLeastOnce())
            ->method('onPreReceived')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnCallback(function (Context $context) use ($expectedLogger) {
                $this->assertSame($expectedLogger, $context->getLogger());
            });

        $this
            ->createQueueConsumer(null, new ChainExtension([$extension, new BreakCycleExtension(1)]))
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->consume();
    }

    public function testShouldCallEachQueueOneByOne(): void
    {
        $anotherMessageProcessor = clone $this->messageProcessor;

        $extension = $this->createExtension();
        $extension->expects(self::exactly(2))
            ->method('onBeforeReceive')
            ->with(self::isInstanceOf(Context::class))
            ->willReturnOnConsecutiveCalls(
                new ReturnCallback(function (Context $context) {
                    $this->assertEquals(self::MESSAGE_PROCESSOR_NAME, $context->getMessageProcessorName());
                    $this->assertEquals(self::QUEUE_NAME, $context->getQueueName());
                }),
                new ReturnCallback(function (Context $context) use ($anotherMessageProcessor) {
                    $this->assertEquals('another_processor', $context->getMessageProcessorName());
                    $this->assertEquals('another_queue', $context->getQueueName());
                })
            );

        $this->messageConsumer
            ->expects(self::exactly(2))
            ->method('receive')
            ->willReturn($this->message);

        $this->messageProcessor
            ->expects(self::any())
            ->method('process')
            ->willReturn(MessageProcessorInterface::ACK);

        $anotherMessageProcessor
            ->expects(self::any())
            ->method('process')
            ->willReturn(MessageProcessorInterface::ACK);

        $messageProcessorRegistry = $this->createMock(MessageProcessorRegistryInterface::class);
        $messageProcessorRegistry
            ->expects(self::exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    [self::MESSAGE_PROCESSOR_NAME, $this->messageProcessor],
                    ['another_processor', $anotherMessageProcessor],
                ]
            );

        $this
            ->createQueueConsumer(null, new BreakCycleExtension(2), null, $messageProcessorRegistry)
            ->bind(self::QUEUE_NAME, self::MESSAGE_PROCESSOR_NAME)
            ->bind('another_queue', 'another_processor')
            ->consume(new ChainExtension([$extension]));
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ExtensionInterface
     */
    private function createExtension(): ExtensionInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        return $this->createMock(ExtensionInterface::class);
    }

    private function createQueueConsumer(
        ConnectionInterface $connection = null,
        ExtensionInterface $extension = null,
        ConsumerState $consumerState = null,
        MessageProcessorRegistryInterface $messageProcessorRegistry = null
    ): QueueConsumer {
        return new QueueConsumer(
            $connection ?? $this->connection,
            $extension ?? new BreakCycleExtension(1),
            $consumerState ?? $this->consumerState,
            $messageProcessorRegistry ?? $this->messageProcessorRegistry,
            0
        );
    }

    private function createSession(): SessionInterface|\PHPUnit\Framework\MockObject\MockObject
    {
        $session = $this->createMock(SessionInterface::class);
        $session
            ->expects(self::any())
            ->method('createQueue')
            ->willReturnCallback(static fn (string $name) => new Queue($name));

        return $session;
    }

    private function createConnection(
        SessionInterface $session
    ): ConnectionInterface|\PHPUnit\Framework\MockObject\MockObject {
        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects(self::any())
            ->method('createSession')
            ->willReturn($session);

        return $connection;
    }
}
