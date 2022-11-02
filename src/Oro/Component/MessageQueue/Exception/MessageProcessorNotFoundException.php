<?php

namespace Oro\Component\MessageQueue\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\RejectMessageExceptionInterface;

/**
 * Should be triggered in case when there is no message processor with specified name.
 * Message with that id should be rejected.
 */
class MessageProcessorNotFoundException extends \LogicException implements RejectMessageExceptionInterface
{
    public static function create(string $processorName): static
    {
        return new static(sprintf('Message processor was not found by processor name "%s"', $processorName));
    }
}
