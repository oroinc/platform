<?php

namespace Oro\Component\MessageQueue\Log\Converter;

use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * Converts a message to array by calling all registered converters and merge theirs results.
 */
class ChainMessageToArrayConverter implements MessageToArrayConverterInterface
{
    /** @var MessageToArrayConverterInterface[] */
    private $converters;

    /**
     * @param MessageToArrayConverterInterface[] $converters
     */
    public function __construct(array $converters)
    {
        $this->converters = $converters;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(MessageInterface $message)
    {
        $result = [];
        foreach ($this->converters as $converter) {
            $result = array_merge($result, $converter->convert($message));
        }

        return $result;
    }
}
