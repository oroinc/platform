<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MessageQueueExtensionTest extends \PHPUnit_Framework_TestCase
{
    use MessageQueueExtension;

    /** @var ContainerInterface */
    private static $container;

    /** @var MessageCollector */
    private static $messageCollector;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass()
    {
        self::$container = null;
        self::$messageCollector = null;
    }

    protected function initClient()
    {
        if (null === self::$container) {
            self::$container = new Container();
            self::$messageCollector = new MessageCollector($this->getMock(MessageProducerInterface::class));
            self::$container->set('oro_message_queue.test.message_collector', self::$messageCollector);
        }
    }

    /**
     * @return ContainerInterface
     */
    protected static function getContainer()
    {
        return self::$container;
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

    public function testAssertMessageSentShouldThrowValidExceptionIfAssertionIsFalse()
    {
        $exception = false;
        try {
            self::assertMessageSent('test topic', 'test message');
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            $exception = $e;
            self::assertContains('Failed asserting that the message', $exception->getMessage());
        }
        if (!$exception) {
            self::fail('\PHPUnit_Framework_ExpectationFailedException expected');
        }
    }

    public function testAssertMessageSentShouldNotThrowExceptionIfAssertionIsTrue()
    {
        $topic = 'test topic';
        $message = 'test message';

        self::$messageCollector->send($topic, $message);

        self::assertMessageSent($topic, $message);
    }

    public function testAssertMessagesSentShouldThrowValidExceptionIfAssertionIsFalse()
    {
        $exception = false;
        try {
            self::assertMessagesSent([['topic' => 'test topic', 'message' => 'test message']]);
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            $exception = $e;
            self::assertContains('Failed asserting that exactly all messages were sent', $exception->getMessage());
        }
        if (!$exception) {
            self::fail('\PHPUnit_Framework_ExpectationFailedException expected');
        }
    }

    public function testAssertMessagesSentShouldNotThrowExceptionIfAssertionIsTrue()
    {
        $topic = 'test topic';
        $message = 'test message';

        self::$messageCollector->send($topic, $message);

        self::assertMessagesSent([['topic' => $topic, 'message' => $message]]);
    }
}
