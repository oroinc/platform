<?php

namespace Oro\Bundle\MessageQueueBundle\EventListener;

use Monolog\Logger;
use Oro\Bundle\MessageQueueBundle\Log\Handler\ConsoleErrorHandler;
use Oro\Component\MessageQueue\Client\ConsumeMessagesCommand as ClientConsumeMessagesCommand;
use Oro\Component\MessageQueue\Consumption\ConsumeMessagesCommand as TransportConsumeMessagesCommand;
use Symfony\Component\Console\Event\ConsoleErrorEvent;

/**
 * Listen `console.error` event if it is consume command send error message to `consumer` channel.
 * Stop error handling for all other listeners, should be processed with highest priority.
 */
class ConsoleErrorListener
{
    /** @var Logger */
    private $logger;

    /**
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ConsoleErrorEvent $event
     */
    public function onConsoleError(ConsoleErrorEvent $event)
    {
        $command = $event->getCommand();
        if ($command instanceof ClientConsumeMessagesCommand
            || $command instanceof TransportConsumeMessagesCommand) {
            foreach ($this->logger->getHandlers() as $handler) {
                if ($handler instanceof ConsoleErrorHandler) {
                    $handler->flush();
                }
            }
            $error = $event->getError();
            $this->logger->error(sprintf('Consuming interrupted, reason: %s', $error->getMessage()));
            $event->stopPropagation();
        }
    }
}
