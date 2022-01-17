<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Consumption\Exception\InvalidMessageBodyException;

/**
 * Validates and normalizes message body according to the specified topic.
 */
interface MessageBodyResolverInterface
{
    /**
     * Validates and normalizes message body according to the specified topic.
     *
     * @param string $topicName
     * @param array|string|float|int|bool|null $body
     * @return array|string
     *
     * @throws InvalidMessageBodyException
     */
    public function resolveBody(string $topicName, array|string|float|int|bool|null $body): array|string;
}
