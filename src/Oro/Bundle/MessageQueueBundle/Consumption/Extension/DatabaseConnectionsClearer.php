<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Closes all active database connections to prevent "Too many connections" exception.
 */
class DatabaseConnectionsClearer implements ClearerInterface
{
    /**
     * @param ContainerInterface $container
     * @param array              $connections [connection name => connection service id, ...]
     */
    public function __construct(ContainerInterface $container, array $connections)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function clear(LoggerInterface $logger)
    {
        // Current logic was moved to DatabaseConnectionsClearExtension,
        // open connections closes after start consumer and after each message was processed.
        // See \Oro\Bundle\MessageQueueBundle\Consumption\Extension\DatabaseConnectionsClearExtension
    }
}
