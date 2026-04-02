<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\ConnectionInterface;

/**
 * Creates driver instances based on the connection type.
 *
 * This factory maps connection classes to their corresponding driver implementations,
 * allowing the message queue system to support multiple transport backends. It instantiates
 * the appropriate driver for a given connection and configuration, enabling flexible
 * integration with different message brokers.
 */
class DriverFactory implements DriverFactoryInterface
{
    /** @var array [connection class => driver class, ...] */
    private $connectionToDriverMap;

    /** @var array [driver class => [extra constructor args, ...], ...] */
    private array $driverArgumentsMap = [];

    /**
     * @param array $connectionToDriverMap [connection class => driver class, ...]
     */
    public function __construct(array $connectionToDriverMap)
    {
        $this->connectionToDriverMap = $connectionToDriverMap;
    }

    /**
     * @param array $driverArgumentsMap [driver class => [extra constructor args, ...], ...]
     */
    public function setDriverArgumentsMap(array $driverArgumentsMap): void
    {
        $this->driverArgumentsMap = $driverArgumentsMap;
    }

    #[\Override]
    public function create(ConnectionInterface $connection, Config $config)
    {
        $connectionClass = get_class($connection);

        if (!array_key_exists($connectionClass, $this->connectionToDriverMap)) {
            throw new \LogicException(sprintf(
                'Unexpected connection instance: "%s", supported "%s"',
                get_class($connection),
                implode('", "', array_keys($this->connectionToDriverMap))
            ));
        }

        $driverClass = $this->connectionToDriverMap[$connectionClass];
        $driverArguments = $this->driverArgumentsMap[$driverClass] ?? [];

        return new $driverClass($connection->createSession(), $config, ...$driverArguments);
    }
}
