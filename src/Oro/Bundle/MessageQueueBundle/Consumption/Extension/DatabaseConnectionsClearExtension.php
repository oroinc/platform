<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Doctrine\DBAL\Connection;
use Oro\Component\MessageQueue\Consumption\AbstractExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Closes all active database connections after consumer was started and after each message was processed.
 */
class DatabaseConnectionsClearExtension extends AbstractExtension
{
    /** @var ContainerInterface */
    private $container;

    /** @var array [connection name => connection service id, ...] */
    private $connections;

    public function __construct(ContainerInterface $container, array $connections)
    {
        $this->container = $container;
        $this->connections = $connections;
    }

    /**
     * {@inheritdoc}
     */
    public function onStart(Context $context): void
    {
        $connections = $this->getAliveConnections();
        $this->close($connections);
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context): void
    {
        $connections = $this->getAliveConnections();

        $logger = $context->getLogger();
        $logger->info('Close database connections.', ['connections' => array_keys($connections)]);
        $this->close($connections);
    }

    /**
     * @return Connection[]
     */
    private function getAliveConnections(): array
    {
        $aliveConnections = [];

        foreach ($this->connections as $name => $serviceId) {
            if ($this->container->initialized($serviceId)) {
                /** @var Connection $connection */
                $connection = $this->container->get($serviceId);
                if ($connection->isConnected()) {
                    $aliveConnections[$name] = $connection;
                }
            }
        }

        return $aliveConnections;
    }

    /**
     * @param Connection[] $connections
     */
    private function close(array $connections): void
    {
        foreach ($connections as $name => $connection) {
            $connection->close();
        }
    }
}
