<?php

namespace Oro\Component\MessageQueue\Log\Converter;

use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * Converts common properties of a message to array.
 */
class MessageToArrayConverter implements MessageToArrayConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert(MessageInterface $message)
    {
        $result = [
            'id'   => $message->getMessageId(),
            'body' => $message->getBody()
        ];

        $properties = $message->getProperties();
        if (!empty($properties)) {
            $result['properties'] = $properties;
        }

        $headers = $message->getHeaders();
        if (!empty($headers)) {
            $result['headers'] = $headers;
        }

        if ($message->isRedelivered()) {
            $result['redelivered'] = true;
        }

        return $result;
    }
}
