<?php

namespace Oro\Bundle\MessageQueueBundle\Platform;

use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverFactoryInterface;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;

/**
 * Add disabled listeners to messages
 */
class OptionalListenerDriverFactory implements DriverFactoryInterface
{
    /** @var DriverFactoryInterface */
    private $driverFactory;

    /** @var OptionalListenerManager */
    private $optionalListenerManager;

    public function __construct(
        DriverFactoryInterface $driverFactory,
        OptionalListenerManager $optionalListenerManager
    ) {
        $this->driverFactory = $driverFactory;
        $this->optionalListenerManager = $optionalListenerManager;
    }

    public function create(ConnectionInterface $connection, Config $config)
    {
        return new OptionalListenerDriver(
            $this->driverFactory->create($connection, $config),
            $this->optionalListenerManager
        );
    }
}
