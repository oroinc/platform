<?php
namespace Oro\Component\MessageQueue\Util;

use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\MessageInterface;

class JSON
{
    /**
     * @param string $string
     *
     * @throws \InvalidArgumentException
     *
     * @return array
     */
    public static function decode($string)
    {
        $decoded = json_decode($string, true);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(sprintf(
                'The malformed json given. Error %s and message %s',
                json_last_error(),
                json_last_error_msg()
            ));
        }

        return $decoded;
    }

    /**
     * @param MessageInterface $message
     *
     * @throws InvalidMessageException
     *
     * @return array
     */
    public static function decodeMessage(MessageInterface $message)
    {
        if ('application/json' != $message->getHeader('content_type')) {
            throw new InvalidMessageException(sprintf(
                'The message content type is not application/json but %s.',
                $message->getHeader('content_type')
            ));
        }

        try {
            return static::decode($message->getBody());
        } catch (\InvalidArgumentException $e) {
            throw new InvalidMessageException(
                'The message content type is a json but the decoded content is not valid json.',
                null,
                $e
            );
        }
    }
}
