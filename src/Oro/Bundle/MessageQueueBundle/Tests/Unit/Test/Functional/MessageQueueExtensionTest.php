<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Test\Functional;

use Monolog\Logger;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Test\Functional\DriverMessageCollector;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProcessorRegistryInterface;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Test\Async\Extension\ConsumedMessagesCollectorExtension;
use Oro\Component\MessageQueue\Transport\Queue;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class MessageQueueExtensionTest extends \PHPUnit\Framework\TestCase
{
    use MessageQueueExtension;

    private static ?ContainerInterface $container = null;
    private static ?MessageCollector $messageCollector = null;
    private static BufferedMessageProducer|\PHPUnit\Framework\MockObject\MockObject|null $bufferedProducer = null;

    protected function setUp(): void
    {
        self::tearDownAfterClass();
        $this->initClient();
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass(): void
    {
        if (self::$container) {
            /** @beforeResetClient */
            self::tearDownMessageCollector();
        }

        self::$container = null;
        self::$messageCollector = null;
        self::$bufferedProducer = null;
    }

    protected function initClient(): void
    {
        if (null === self::$container) {
            self::$container = new Container();
            $driverMessageCollector = new DriverMessageCollector($this->createMock(DriverInterface::class));
            $messageProducer = $this->createMock(MessageProducerInterface::class);
            $messageProducer
                ->expects(self::any())
                ->method('send')
                ->willReturnCallback(function (string $topic, array|string $messageBody) use ($driverMessageCollector) {
                    $message = new Message($messageBody);
                    $message->setMessageId(uniqid('oro.', true));
                    $message->setProperty(Config::PARAMETER_TOPIC_NAME, $topic);
                    $driverMessageCollector->send(new Queue('oro.default'), $message);
                });
            self::$messageCollector = new MessageCollector(
                $messageProducer,
                $driverMessageCollector
            );
            self::$container->set('oro_message_queue.test.message_collector', self::$messageCollector);
            self::$bufferedProducer = $this->createMock(BufferedMessageProducer::class);
            self::$container->set('oro_message_queue.client.buffered_message_producer', self::$bufferedProducer);
            self::$container->set(
                'oro_message_queue.test.async.extension.consumed_messages_collector',
                new ConsumedMessagesCollectorExtension(
                    $this->createMock(MessageProcessorRegistryInterface::class),
                    $this->createMock(Logger::class)
                )
            );
        }

        /** @afterInitClient */
        $this->setUpMessageCollector();
    }

    protected static function getContainer(): ContainerInterface
    {
        return self::$container;
    }

    public function testShouldAllowEnableMessageBuffering(): void
    {
        self::$bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        self::$bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        self::enableMessageBuffering();
    }

    public function testShouldNotEnableMessageBufferingWhenItIsAlreadyEnabled(): void
    {
        self::$bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        self::$bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        self::enableMessageBuffering();
    }

    public function testShouldAllowDisableMessageBuffering(): void
    {
        self::$bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        self::$bufferedProducer->expects(self::once())
            ->method('disableBuffering');

        self::disableMessageBuffering();
    }

    public function testShouldNotDisableMessageBufferingWhenItIsAlreadyDisabled(): void
    {
        self::$bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        self::$bufferedProducer->expects(self::never())
            ->method('disableBuffering');

        self::disableMessageBuffering();
    }

    public function testShouldAllowFlushMessagesBuffer(): void
    {
        self::$bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        self::$bufferedProducer->expects(self::once())
            ->method('flushBuffer');

        self::flushMessagesBuffer();
    }

    public function testShouldNotFlushMessagesBufferWhenBufferingIsNotEnabled(): void
    {
        self::$bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        self::$bufferedProducer->expects(self::never())
            ->method('flushBuffer');

        self::flushMessagesBuffer();
    }

    public function testShouldAllowGetMessageCollector(): void
    {
        self::assertSame(self::$messageCollector, self::getMessageCollector());
    }

    public function testShouldAllowGetSentMessages(): void
    {
        $topic = 'test topic';
        $message = 'test message';

        self::$messageCollector->send($topic, $message);

        self::assertEquals(
            [
                ['topic' => $topic, 'message' => $message],
            ],
            self::getSentMessages()
        );
    }

    public function testShouldAllowSendMessageViaMessageProducerAlias(): void
    {
        $topic = 'test topic';
        $message = 'test message';

        self::getMessageProducer()->send($topic, $message);

        self::assertEquals(
            [
                ['topic' => $topic, 'message' => $message],
            ],
            self::getSentMessages()
        );
    }

    public function testAssertMessageSentShouldThrowValidExceptionIfAssertionIsFalse(): void
    {
        $exception = false;
        try {
            self::assertMessageSent('test topic', 'test message');
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            self::assertStringContainsString('Failed asserting that the message', $exception->getMessage());
            self::assertStringContainsString('All sent messages', $exception->getMessage());
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertMessageSentShouldNotThrowExceptionIfAssertionIsTrue(): void
    {
        $topic = 'test topic';
        $message = 'test message';

        self::$messageCollector->send($topic, $message);

        self::assertMessageSent($topic, $message);
    }

    public function testAssertMessageSentWithoutMessageBodyShouldThrowValidExceptionIfAssertionIsFalse(): void
    {
        $exception = false;
        try {
            self::assertMessageSent('test topic');
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            self::assertStringContainsString('Failed asserting that the message', $exception->getMessage());
            self::assertStringContainsString('All sent messages', $exception->getMessage());
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertMessageSentWithoutMessageBodyShouldNotThrowExceptionIfAssertionIsTrue(): void
    {
        $topic = 'test topic';

        self::$messageCollector->send($topic, 'test message');

        self::assertMessageSent($topic);
    }

    public function testAssertMessagesSentShouldThrowValidExceptionIfOneOfMessageAssertionIsFalse(): void
    {
        $topic = 'test topic';
        $message1 = 'test message 1';
        $message2 = 'test message 2';

        self::$messageCollector->send($topic, $message1);
        self::$messageCollector->send($topic, $message2);

        $exception = false;
        try {
            self::assertMessagesSent($topic, [$message1, 'another message']);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            self::assertStringContainsString('Failed asserting that the message', $exception->getMessage());
            self::assertStringContainsString('All sent messages', $exception->getMessage());
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertMessagesSentShouldThrowValidExceptionIfCountAssertionIsFalse(): void
    {
        $topic = 'test topic';
        $message1 = 'test message 1';
        $message2 = 'test message 2';

        self::$messageCollector->send($topic, $message1);
        self::$messageCollector->send($topic, $message2);

        $exception = false;
        try {
            self::assertMessagesSent($topic, [$message2]);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            self::assertStringContainsString(
                'Failed asserting that exactly given messages were sent to "test topic" topic',
                $exception->getMessage()
            );
            self::assertStringContainsString(
                'actual size 2 matches expected size 1',
                $exception->getMessage()
            );
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertMessagesSentShouldNotThrowExceptionIfAssertionIsTrue(): void
    {
        $topic = 'test topic';
        $message1 = 'test message 1';
        $message2 = 'test message 2';

        $anotherTopic = 'another topic';
        $anotherMessage = 'another message';

        self::$messageCollector->send($topic, $message1);
        self::$messageCollector->send($topic, $message2);
        self::$messageCollector->send($anotherTopic, $anotherMessage);

        self::assertMessagesSent($topic, [$message1, $message2]);
        // test that send order is not taken into account
        self::assertMessagesSent($topic, [$message2, $message1]);
        // do test for another topic
        self::assertMessagesSent($anotherTopic, [$anotherMessage]);
    }

    public function testAssertMessagesCountShouldThrowValidExceptionIfCountAssertionIsFalse(): void
    {
        $topic = 'test topic';

        self::$messageCollector->send($topic, 'test message 1');
        self::$messageCollector->send($topic, 'test message 2');

        $exception = false;
        try {
            self::assertMessagesCount($topic, 1);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            self::assertStringContainsString(
                'Failed asserting that the given number of messages were sent to "test topic" topic',
                $exception->getMessage()
            );
            self::assertStringContainsString(
                'actual size 2 matches expected size 1',
                $exception->getMessage()
            );
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertMessagesCountShouldNotThrowExceptionIfAssertionIsTrue(): void
    {
        $topic = 'test topic';
        $anotherTopic = 'another topic';

        self::$messageCollector->send($topic, 'test message 1');
        self::$messageCollector->send($topic, 'test message 2');
        self::$messageCollector->send($anotherTopic, 'another message');

        self::assertMessagesCount($topic, 2);
        // do test for another topic
        self::assertMessagesCount($anotherTopic, 1);
    }

    public function testAssertMessagesCountShouldNotThrowExceptionIfZeroAssertionIsTrue(): void
    {
        self::assertMessagesCount('test topic', 0);
    }

    public function testAssertCountMessagesShouldThrowValidExceptionIfCountAssertionIsFalse(): void
    {
        $topic = 'test topic';

        self::$messageCollector->send($topic, 'test message 1');
        self::$messageCollector->send($topic, 'test message 2');

        $exception = false;
        try {
            self::assertCountMessages($topic, 1);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            self::assertStringContainsString(
                'Failed asserting that the given number of messages were sent to "test topic" topic',
                $exception->getMessage()
            );
            self::assertStringContainsString(
                'actual size 2 matches expected size 1',
                $exception->getMessage()
            );
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertCountMessagesShouldNotThrowExceptionIfAssertionIsTrue(): void
    {
        $topic = 'test topic';
        $anotherTopic = 'another topic';

        self::$messageCollector->send($topic, 'test message 1');
        self::$messageCollector->send($topic, 'test message 2');
        self::$messageCollector->send($anotherTopic, 'another message');

        self::assertCountMessages($topic, 2);
        // do test for another topic
        self::assertCountMessages($anotherTopic, 1);
    }

    public function testAssertCountMessagesShouldNotThrowExceptionIfZeroAssertionIsTrue(): void
    {
        self::assertCountMessages('test topic', 0);
    }

    public function testAssertMessagesEmptyShouldThrowValidExceptionIfAssertionIsFalse(): void
    {
        $topic = 'test topic';

        self::$messageCollector->send($topic, 'test message');

        $exception = false;
        try {
            self::assertMessagesEmpty($topic);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            self::assertStringContainsString(
                'Failed asserting that exactly given messages were sent to "test topic" topic',
                $exception->getMessage()
            );
            self::assertStringContainsString(
                'actual size 1 matches expected size 0',
                $exception->getMessage()
            );
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertMessagesEmptyShouldNotThrowExceptionIfAssertionIsTrue(): void
    {
        self::assertMessagesEmpty('test topic');
    }

    public function testAssertEmptyMessagesShouldThrowValidExceptionIfAssertionIsFalse(): void
    {
        $topic = 'test topic';

        self::$messageCollector->send($topic, 'test message');

        $exception = false;
        try {
            self::assertEmptyMessages($topic);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            self::assertStringContainsString(
                'Failed asserting that exactly given messages were sent to "test topic" topic',
                $exception->getMessage()
            );
            self::assertStringContainsString(
                'actual size 1 matches expected size 0',
                $exception->getMessage()
            );
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertEmptyMessagesShouldNotThrowExceptionIfAssertionIsTrue(): void
    {
        self::assertEmptyMessages('test topic');
    }

    public function testAssertAllMessagesSentShouldThrowValidExceptionIfAssertionIsFalse(): void
    {
        $exception = false;
        try {
            self::assertAllMessagesSent([['topic' => 'test topic', 'message' => 'test message']]);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            self::assertStringContainsString(
                'Failed asserting that exactly all messages were sent',
                $exception->getMessage()
            );
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertAllMessagesSentShouldNotThrowExceptionIfAssertionIsTrue(): void
    {
        $topic = 'test topic';
        $message = 'test message';

        self::$messageCollector->send($topic, $message);

        self::assertAllMessagesSent([['topic' => $topic, 'message' => $message]]);
    }
}
