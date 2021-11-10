<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Consumption\NullMessageProcessor;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Registry of all message queue processors.
 */
class MessageProcessorRegistry extends ServiceLocator implements MessageProcessorRegistryInterface
{
    /**
     * {@inheritdoc}
     */
    public function get(string $id): MessageProcessorInterface
    {
        if (!$id || !$this->has($id)) {
            return new NullMessageProcessor($id);
        }

        return parent::get($id);
    }
}
