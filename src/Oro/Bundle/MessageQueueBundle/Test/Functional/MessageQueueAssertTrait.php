<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Component\MessageQueue\Client\Message;

/**
 * Provides useful assertion methods for the message queue related tests.
 * It is expected that this trait will be used in classes that have "getContainer" method.
 * E.g. classes derived from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.
 */
trait MessageQueueAssertTrait
{
    /**
     * Asserts that a message was sent to a topic.
     *
     * @param string               $expectedTopic   The expected topic name
     * @param string|array|Message $expectedMessage The expected message
     */
    protected static function assertMessageSent($expectedTopic, $expectedMessage)
    {
        $constraint = new SentMessageConstraint(
            ['topic' => $expectedTopic, 'message' => $expectedMessage]
        );

        self::assertThat(self::getSentMessages(), $constraint);
    }

    /**
     * Asserts that messages were sent to a topic.
     * The send order is not taken into account.
     *
     * @param string $expectedTopic    The expected topic name
     * @param array  $expectedMessages The expected messages
     *                                 Each message can be string, array or instance of Message class
     */
    protected static function assertMessagesSent($expectedTopic, array $expectedMessages)
    {
        foreach ($expectedMessages as $expectedMessage) {
            self::assertMessageSent($expectedTopic, $expectedMessage);
        }

        $topicMessages = [];
        $sentMessages = self::getSentMessages();
        foreach ($sentMessages as $sentMessage) {
            if ($sentMessage['topic'] === $expectedTopic) {
                $topicMessages[] = $sentMessage['message'];
            }
        }
        self::assertCount(
            count($expectedMessages),
            $topicMessages,
            sprintf('Failed asserting that exactly given messages were sent to "%s" topic.', $expectedTopic)
        );
    }

    /**
     * Asserts that no any message was sent to a topic.
     *
     * @param string $expectedTopic The expected topic name
     */
    protected static function assertEmptyMessages($expectedTopic)
    {
        self::assertMessagesSent($expectedTopic, []);
    }

    /**
     * Asserts that exactly given messages were sent.
     * NOTE: use this assertion with caution because it is possible
     * that messages not related to a testing functionality were sent as well.
     *
     * @param array $expected [['topic' => topic name, 'message' => message], ...]
     */
    protected static function assertAllMessagesSent(array $expected)
    {
        $constraint = new SentMessagesConstraint($expected);

        self::assertThat(self::getSentMessages(), $constraint);
    }

    /**
     * Gets all sent messages.
     *
     * @return array [['topic' => topic name, 'message' => message (string|array|Message)], ...]
     */
    protected static function getSentMessages()
    {
        return self::getMessageCollector()->getSentMessages();
    }

    /**
     * Gets an object responsible to collect all sent messages.
     *
     * @return MessageCollector
     */
    protected static function getMessageCollector()
    {
        return self::getContainer()->get('oro_message_queue.test.message_collector');
    }
}
