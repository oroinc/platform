<?php

namespace Oro\Component\MessageQueue\Log\Converter;

use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * This interface should be implemented by classes that converts a message to its array representation.
 */
interface MessageToArrayConverterInterface
{
    /**
     * Converts the given message to its array representation.
     */
    public function convert(MessageInterface $message): array;
}
