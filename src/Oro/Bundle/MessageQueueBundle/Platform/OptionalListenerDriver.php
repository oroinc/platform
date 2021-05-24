<?php

namespace Oro\Bundle\MessageQueueBundle\Platform;

use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\PlatformBundle\Provider\Console\OptionalListenersGlobalOptionsProvider;
use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;

/**
 * Add disabled listeners to messages
 */
class OptionalListenerDriver implements DriverInterface
{
    /** @var DriverInterface */
    private $driver;

    /** @var OptionalListenerManager */
    private $optionalListenerManager;

    public function __construct(
        DriverInterface $driver,
        OptionalListenerManager $optionalListenerManager
    ) {
        $this->driver = $driver;
        $this->optionalListenerManager = $optionalListenerManager;
    }

    public function send(QueueInterface $queue, Message $message): void
    {
        $disabledListeners = $this->optionalListenerManager->getDisabledListeners();
        if ($disabledListeners) {
            $message->setProperty(
                OptionalListenersGlobalOptionsProvider::DISABLE_OPTIONAL_LISTENERS,
                json_encode($disabledListeners)
            );
        }

        $this->driver->send($queue, $message);
    }

    public function createTransportMessage(): MessageInterface
    {
        return $this->driver->createTransportMessage();
    }

    public function createQueue($queueName): QueueInterface
    {
        return $this->driver->createQueue($queueName);
    }

    public function getConfig(): Config
    {
        return $this->driver->getConfig();
    }
}
