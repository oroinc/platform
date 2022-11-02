<?php

namespace Oro\Component\MessageQueue\Log\Converter;

use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * Converts a message to array by calling all registered converters and merge theirs results.
 */
class ChainMessageToArrayConverter implements MessageToArrayConverterInterface
{
    /** @var iterable|MessageToArrayConverterInterface[] */
    private $converters;

    /**
     * @param iterable|MessageToArrayConverterInterface[] $converters
     */
    public function __construct(iterable $converters)
    {
        $this->converters = $converters;
    }

    /**
     * {@inheritdoc}
     */
    public function convert(MessageInterface $message): array
    {
        $items = [];
        foreach ($this->converters as $converter) {
            $items[] = $converter->convert($message);
        }

        return array_merge(...$items);
    }
}
