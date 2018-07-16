<?php

namespace Oro\Component\MessageQueue\Log\Converter;

use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\MessageInterface;

/**
 * Converts properties specific for DBAL message to array.
 */
class DbalMessageToArrayConverter implements MessageToArrayConverterInterface
{
    /**
     * {@inheritdoc}
     */
    public function convert(MessageInterface $message)
    {
        $result = [];
        if ($message instanceof DbalMessage) {
            $result['priority'] = $message->getPriority();
            $delay = $message->getDelay();
            if (null !== $delay) {
                $result['delay'] = $delay;
            }
        }

        return $result;
    }
}
