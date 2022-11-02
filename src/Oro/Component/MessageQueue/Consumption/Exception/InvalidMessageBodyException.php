<?php

namespace Oro\Component\MessageQueue\Consumption\Exception;

use Oro\Component\MessageQueue\Util\JSON;

/**
 * When message body is invalid.
 */
class InvalidMessageBodyException extends \RuntimeException implements RejectMessageExceptionInterface
{
    public static function create(
        string $error,
        string $topicName,
        array|string|float|int|bool|null $body,
        \Throwable $previousException = null
    ): self {
        $body = is_array($body) ? JSON::encode($body) : (string) $body;

        return new self(
            sprintf('Message of topic "%s" has invalid body: %s. %s', $topicName, $body, $error),
            0,
            $previousException
        );
    }
}
