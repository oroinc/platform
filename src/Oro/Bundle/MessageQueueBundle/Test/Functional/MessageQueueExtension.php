<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;

/**
 * It is expected that this trait will be used in classes that have "getContainer" static method.
 * E.g. classes derived from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.
 */
trait MessageQueueExtension
{
    use MessageQueueAssertTrait;

    /**
     * Removes all sent messages.
     *
     * @afterInitClient
     */
    public function setUpMessageCollector()
    {
        self::clearMessageCollector();
    }

    /**
     * Removes all sent messages.
     *
     * After triggered after client removed
     *
     * @beforeResetClient
     */
    protected static function tearDownMessageCollector(): void
    {
        self::clearMessageCollector();
        self::disableMessageBuffering();
    }

    protected static function getBufferedMessageProducer(): BufferedMessageProducer
    {
        return self::getContainer()->get('oro_message_queue.client.buffered_message_producer');
    }

    /**
     * Enables the buffering of sent messages.
     */
    protected static function enableMessageBuffering(): void
    {
        $bufferedProducer = self::getBufferedMessageProducer();
        if (!$bufferedProducer->isBufferingEnabled()) {
            $bufferedProducer->enableBuffering();
        }
    }

    /**
     * Disables the buffering of sent messages.
     */
    protected static function disableMessageBuffering(): void
    {
        $bufferedProducer = self::getBufferedMessageProducer();
        if ($bufferedProducer->isBufferingEnabled()) {
            $bufferedProducer->disableBuffering();
        }
    }

    /**
     * Flushes buffered sent messages.
     */
    protected static function flushMessagesBuffer(): void
    {
        $bufferedProducer = self::getBufferedMessageProducer();
        if ($bufferedProducer->isBufferingEnabled()) {
            $bufferedProducer->flushBuffer();
        }
    }
}
