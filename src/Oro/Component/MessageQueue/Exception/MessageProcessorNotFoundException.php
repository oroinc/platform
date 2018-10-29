<?php

namespace Oro\Component\MessageQueue\Exception;

use Oro\Component\MessageQueue\Consumption\Exception\RejectMessageExceptionInterface;

/**
 * Should be triggered in case when there is no message processor with specified name
 * Message with that id should be rejected.
 */
class MessageProcessorNotFoundException extends \LogicException implements RejectMessageExceptionInterface
{
    /**
     * @param string $processorName
     * @return MessageProcessorNotFoundException
     */
    public static function create(string $processorName)
    {
        return new static(sprintf('MessageProcessor was not found. processorName: "%s"', $processorName));
    }
}
