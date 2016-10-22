<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Bundle\MessageQueueBundle\Test\Assert\AbstractMessageQueueAssertTrait;

/**
 * Provides useful assertion methods for the message queue related functional tests.
 * It is expected that this trait will be used in classes that have "getContainer" static method.
 * E.g. classes derived from Oro\Bundle\TestFrameworkBundle\Test\WebTestCase.
 */
trait MessageQueueAssertTrait
{
    use AbstractMessageQueueAssertTrait;

    /**
     * Gets an object responsible to collect all sent messages.
     *
     * @return MessageCollector
     */
    protected static function getMessageCollector()
    {
        return self::getContainer()->get('oro_message_queue.test.message_collector');
    }

    /**
     * Gets the message producer.
     * Use this alias for "getMessageCollector" method in case if it makes your tests more intuitive.
     *
     * @return MessageCollector
     */
    protected static function getMessageProducer()
    {
        return self::getMessageCollector();
    }
}
