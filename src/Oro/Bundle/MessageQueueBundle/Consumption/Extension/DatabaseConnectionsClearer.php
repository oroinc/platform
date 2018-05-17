<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Closes all active database connections to prevent "Too many connections" exception.
 */
class DatabaseConnectionsClearer implements ClearerInterface
{
    /** @var ContainerInterface */
    private $container;

    /** @var array [connection name => connection service id, ...] */
    private $connections;

    /**
     * @param ContainerInterface $container
     * @param array              $connections [connection name => connection service id, ...]
     */
    public function __construct(ContainerInterface $container, array $connections)
    {
        $this->container = $container;
        $this->connections = $connections;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(LoggerInterface $logger)
    {
        foreach ($this->connections as $name => $serviceId) {
            if ($this->container->initialized($serviceId)) {
                $connection = $this->container->get($serviceId);
                if ($connection->isConnected()) {
                    $logger->info(sprintf('Close database connection "%s"', $name));
                    $connection->close();
                }
            }
        }
    }
}
