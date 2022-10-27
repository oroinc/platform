<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * Interface for message processor registry.
 */
interface MessageProcessorRegistryInterface extends ServiceProviderInterface
{
    public function get(string $id): MessageProcessorInterface;
}
