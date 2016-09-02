<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\ConnectionInterface;

class DriverFactory
{
    /**
     * @var string[]
     */
    private $connectionToDriverMap;

    /**
     * @param array $connectionToDriverMap The array must have next structure ['connectionClass' => 'driverClass']
     */
    public function __construct(array $connectionToDriverMap)
    {
        $this->connectionToDriverMap = $connectionToDriverMap;
    }

    /**
     * @param ConnectionInterface $connection
     * @param Config     $config
     *
     * @return DriverInterface
     */
    public function create(ConnectionInterface $connection, Config $config)
    {
        $connectionClass = get_class($connection);

        if (array_key_exists($connectionClass, $this->connectionToDriverMap)) {
            $driverClass = $this->connectionToDriverMap[$connectionClass];

            return new $driverClass($connection->createSession(), $config);
        } else {
            throw new \LogicException(sprintf(
                'Unexpected connection instance: "%s", supported "%s"',
                get_class($connection),
                implode('", "', array_keys($this->connectionToDriverMap))
            ));
        }
    }
}
