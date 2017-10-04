<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Psr\Log\LoggerInterface;

/**
 * An interface for classes that implements a logic to be executed to clear
 * the consumer state after execution of each non-persistent processor.
 * For details see "Resources/doc/container_in_consumer.md".
 * IMPORTANT: Do not inject non-persistent services into clearers because all clearers
 * are persistent and they are not removed from the memory. Inject the service container
 * in case if a clearer need some non-persistent services. Also do not forget
 * to call "container->initialized()" to avoid unnecessary initialization of services.
 */
interface ClearerInterface
{
    /**
     * Clears the consumer state.
     *
     * @param LoggerInterface $logger
     */
    public function clear(LoggerInterface $logger);
}
