<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Unit;

use Oro\Bundle\MessageQueueBundle\Test\Assert\AbstractMessageQueueAssertTrait;
use Oro\Bundle\MessageQueueBundle\Test\MessageCollector;

/**
 * Provides useful assertion methods for the message queue related unit tests.
 */
trait MessageQueueAssertTrait
{
    use AbstractMessageQueueAssertTrait;

    /**
     * @var MessageCollector|null
     */
    private static $messageCollector;

    /**
     * Unsets the message collector if needed.
     *
     * @afterClass
     */
    public static function tearDownAfterClassMessageCollector()
    {
        if (isset(self::$messageCollector)) {
            self::$messageCollector = null;
        }
    }

    /**
     * Gets an object responsible to collect all sent messages.
     *
     * @return MessageCollector
     */
    protected static function getMessageCollector()
    {
        if (!isset(self::$messageCollector)) {
            self::$messageCollector = new MessageCollector();
        }

        return self::$messageCollector;
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
