<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Component\MessageQueue\Client\Message;

/**
 * It is expected that this trait will be used in classes that have "getContainer" method.
 * E.g. classes derived from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.
 */
trait MessageQueueExtension
{
    /**
     * Enables the collecting of messages before each test.
     *
     * @before
     */
    public function setUpMessageCollector()
    {
        self::getMessageCollector()
            ->enable();
    }

    /**
     * Removes all sent messages and disables the collecting of new messages after each test.
     * The disabling of the collector is needed because it is possible that exist
     * functional test that produce messages, but they do not need to test it,
     * and, as result, this extension might not be added to such tests.
     *
     * @after
     */
    public function tearDownMessageCollector()
    {
        self::getMessageCollector()
            ->clear()
            ->disable();
    }

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
     * Asserts that exactly given messages were sent.
     *
     * @param array $expected [['topic' => topic name, 'message' => message], ...]
     */
    protected static function assertMessagesSent(array $expected)
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
