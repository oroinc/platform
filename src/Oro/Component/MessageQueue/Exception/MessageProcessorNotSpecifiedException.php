<?php

namespace Oro\Component\MessageQueue\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\RejectMessageExceptionInterface;

/**
 * Should be triggered in case when message processor is not specified when trying to consume a message.
 * Message with that id should be rejected.
 */
class MessageProcessorNotSpecifiedException extends \LogicException implements RejectMessageExceptionInterface
{
    public static function create(): static
    {
        return new static('Message processor is not specified');
    }
}
