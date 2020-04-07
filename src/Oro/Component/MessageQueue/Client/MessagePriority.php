<?php

namespace Oro\Component\MessageQueue\Client;

/**
 * A message priority helper class.
 * Contains message priority mapping.
 */
class MessagePriority
{
    public const VERY_LOW = 'oro.message_queue.client.very_low_message_priority';
    public const LOW = 'oro.message_queue.client.low_message_priority';
    public const NORMAL = 'oro.message_queue.client.normal_message_priority';
    public const HIGH = 'oro.message_queue.client.high_message_priority';
    public const VERY_HIGH = 'oro.message_queue.client.very_high_message_priority';

    /** @var array */
    public static $map = [
        MessagePriority::VERY_LOW => 0,
        MessagePriority::LOW => 1,
        MessagePriority::NORMAL => 2,
        MessagePriority::HIGH => 3,
        MessagePriority::VERY_HIGH => 4,
    ];

    /**
     * @param string $priority
     * @return int
     */
    public static function getMessagePriority(string $priority): int
    {
        if (!array_key_exists($priority, self::$map)) {
            throw new \InvalidArgumentException(sprintf(
                'Given priority could not be converted to transport\'s one. Got: %s',
                $priority
            ));
        }

        return self::$map[$priority];
    }
}
