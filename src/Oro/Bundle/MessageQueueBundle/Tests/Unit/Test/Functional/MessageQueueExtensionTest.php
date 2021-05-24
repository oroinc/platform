<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Test\Functional;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\MessageQueueBundle\Client\MessageFilterInterface;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
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

    /** @var ContainerInterface */
    private static $container;

    /** @var MessageCollector */
    private static $messageCollector;

    /** @var MessageFilterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private static $messageFilter;

    /** @var BufferedMessageProducer|\PHPUnit\Framework\MockObject\MockObject */
    private static $bufferedProducer;

    /**
     * {@inheritdoc}
     */
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
        self::$messageFilter = null;
        self::$bufferedProducer = null;
    }

    protected function initClient()
    {
        if (null === self::$container) {
            self::$container = new Container();
            self::$messageFilter = $this->createMock(MessageFilterInterface::class);
            self::$messageCollector = new MessageCollector(
                $this->createMock(MessageProducerInterface::class),
                self::$messageFilter
            );
            self::$container->set('oro_message_queue.test.message_collector', self::$messageCollector);
            self::$bufferedProducer = $this->createMock(BufferedMessageProducer::class);
            self::$container->set('oro_message_queue.client.buffered_message_producer', self::$bufferedProducer);
        }

        /** @afterInitClient */
        $this->setUpMessageCollector();
    }

    /**
     * @return ContainerInterface
     */
    protected static function getContainer()
    {
        return self::$container;
    }

    public function testShouldAllowEnableMessageBuffering()
    {
        self::$bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        self::$bufferedProducer->expects(self::once())
            ->method('enableBuffering');

        self::enableMessageBuffering();
    }

    public function testShouldNotEnableMessageBufferingWhenItIsAlreadyEnabled()
    {
        self::$bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        self::$bufferedProducer->expects(self::never())
            ->method('enableBuffering');

        self::enableMessageBuffering();
    }

    public function testShouldAllowDisableMessageBuffering()
    {
        self::$bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        self::$bufferedProducer->expects(self::once())
            ->method('disableBuffering');

        self::disableMessageBuffering();
    }

    public function testShouldNotDisableMessageBufferingWhenItIsAlreadyDisabled()
    {
        self::$bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        self::$bufferedProducer->expects(self::never())
            ->method('disableBuffering');

        self::disableMessageBuffering();
    }

    public function testShouldAllowFlushMessagesBuffer()
    {
        self::$bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(true);
        self::$bufferedProducer->expects(self::once())
            ->method('flushBuffer');

        self::flushMessagesBuffer();
    }

    public function testShouldNotFlushMessagesBufferWhenBufferingIsNotEnabled()
    {
        self::$bufferedProducer->expects(self::once())
            ->method('isBufferingEnabled')
            ->willReturn(false);
        self::$bufferedProducer->expects(self::never())
            ->method('flushBuffer');

        self::flushMessagesBuffer();
    }

    public function testShouldAllowGetMessageCollector()
    {
        self::assertSame(self::$messageCollector, self::getMessageCollector());
    }

    public function testShouldAllowGetSentMessages()
    {
        $topic = 'test topic';
        $message = 'test message';

        self::$messageCollector->send($topic, $message);

        self::assertEquals(
            [
                ['topic' => $topic, 'message' => $message]
            ],
            self::getSentMessages()
        );
    }

    public function testShouldAllowSendMessageViaMessageProducerAlias()
    {
        $topic = 'test topic';
        $message = 'test message';

        self::getMessageProducer()->send($topic, $message);

        self::assertEquals(
            [
                ['topic' => $topic, 'message' => $message]
            ],
            self::getSentMessages()
        );
    }

    public function testAssertMessageSentShouldThrowValidExceptionIfAssertionIsFalse()
    {
        $exception = false;
        try {
            self::assertMessageSent('test topic', 'test message');
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            static::assertStringContainsString('Failed asserting that the message', $exception->getMessage());
            static::assertStringContainsString('All sent messages', $exception->getMessage());
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertMessageSentShouldNotThrowExceptionIfAssertionIsTrue()
    {
        $topic = 'test topic';
        $message = 'test message';

        self::$messageCollector->send($topic, $message);

        self::assertMessageSent($topic, $message);
    }

    public function testAssertMessageSentWithoutMessageBodyShouldThrowValidExceptionIfAssertionIsFalse()
    {
        $exception = false;
        try {
            self::assertMessageSent('test topic');
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            static::assertStringContainsString('Failed asserting that the message', $exception->getMessage());
            static::assertStringContainsString('All sent messages', $exception->getMessage());
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertMessageSentWithoutMessageBodyShouldNotThrowExceptionIfAssertionIsTrue()
    {
        $topic = 'test topic';

        self::$messageCollector->send($topic, 'test message');

        self::assertMessageSent($topic);
    }

    public function testAssertMessagesSentShouldThrowValidExceptionIfOneOfMessageAssertionIsFalse()
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
            static::assertStringContainsString('Failed asserting that the message', $exception->getMessage());
            static::assertStringContainsString('All sent messages', $exception->getMessage());
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertMessagesSentShouldThrowValidExceptionIfCountAssertionIsFalse()
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
            static::assertStringContainsString(
                'Failed asserting that exactly given messages were sent to "test topic" topic',
                $exception->getMessage()
            );
            static::assertStringContainsString(
                'actual size 2 matches expected size 1',
                $exception->getMessage()
            );
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertMessagesSentShouldNotThrowExceptionIfAssertionIsTrue()
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

    public function testAssertMessagesCountShouldThrowValidExceptionIfCountAssertionIsFalse()
    {
        $topic = 'test topic';

        self::$messageCollector->send($topic, 'test message 1');
        self::$messageCollector->send($topic, 'test message 2');

        $exception = false;
        try {
            self::assertMessagesCount($topic, 1);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            static::assertStringContainsString(
                'Failed asserting that the given number of messages were sent to "test topic" topic',
                $exception->getMessage()
            );
            static::assertStringContainsString(
                'actual size 2 matches expected size 1',
                $exception->getMessage()
            );
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertMessagesCountShouldNotThrowExceptionIfAssertionIsTrue()
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

    public function testAssertMessagesCountShouldNotThrowExceptionIfZeroAssertionIsTrue()
    {
        self::assertMessagesCount('test topic', 0);
    }

    public function testAssertCountMessagesShouldThrowValidExceptionIfCountAssertionIsFalse()
    {
        $topic = 'test topic';

        self::$messageCollector->send($topic, 'test message 1');
        self::$messageCollector->send($topic, 'test message 2');

        $exception = false;
        try {
            self::assertCountMessages($topic, 1);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            static::assertStringContainsString(
                'Failed asserting that the given number of messages were sent to "test topic" topic',
                $exception->getMessage()
            );
            static::assertStringContainsString(
                'actual size 2 matches expected size 1',
                $exception->getMessage()
            );
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertCountMessagesShouldNotThrowExceptionIfAssertionIsTrue()
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

    public function testAssertCountMessagesShouldNotThrowExceptionIfZeroAssertionIsTrue()
    {
        self::assertCountMessages('test topic', 0);
    }

    public function testAssertMessagesEmptyShouldThrowValidExceptionIfAssertionIsFalse()
    {
        $topic = 'test topic';

        self::$messageCollector->send($topic, 'test message');

        $exception = false;
        try {
            self::assertMessagesEmpty($topic);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            static::assertStringContainsString(
                'Failed asserting that exactly given messages were sent to "test topic" topic',
                $exception->getMessage()
            );
            static::assertStringContainsString(
                'actual size 1 matches expected size 0',
                $exception->getMessage()
            );
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertMessagesEmptyShouldNotThrowExceptionIfAssertionIsTrue()
    {
        self::assertMessagesEmpty('test topic');
    }

    public function testAssertEmptyMessagesShouldThrowValidExceptionIfAssertionIsFalse()
    {
        $topic = 'test topic';

        self::$messageCollector->send($topic, 'test message');

        $exception = false;
        try {
            self::assertEmptyMessages($topic);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            static::assertStringContainsString(
                'Failed asserting that exactly given messages were sent to "test topic" topic',
                $exception->getMessage()
            );
            static::assertStringContainsString(
                'actual size 1 matches expected size 0',
                $exception->getMessage()
            );
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertEmptyMessagesShouldNotThrowExceptionIfAssertionIsTrue()
    {
        self::assertEmptyMessages('test topic');
    }

    public function testAssertAllMessagesSentShouldThrowValidExceptionIfAssertionIsFalse()
    {
        $exception = false;
        try {
            self::assertAllMessagesSent([['topic' => 'test topic', 'message' => 'test message']]);
        } catch (\PHPUnit\Framework\ExpectationFailedException $e) {
            $exception = $e;
            static::assertStringContainsString(
                'Failed asserting that exactly all messages were sent',
                $exception->getMessage()
            );
        }
        if (!$exception) {
            self::fail('\PHPUnit\Framework\ExpectationFailedException expected');
        }
    }

    public function testAssertAllMessagesSentShouldNotThrowExceptionIfAssertionIsTrue()
    {
        $topic = 'test topic';
        $message = 'test message';

        self::$messageCollector->send($topic, $message);

        self::assertAllMessagesSent([['topic' => $topic, 'message' => $message]]);
    }
}
