<?php

namespace Oro\Bundle\MessageQueueBundle\EventListener;

use Oro\Component\MessageQueue\Client\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Consumption\ConsumeMessagesCommand as TransportConsumeMessagesCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Configures default logger when fatal error was occurred (such as "Allowed memory size...").
 */
class ConsoleFatalErrorListener implements EventSubscriberInterface
{
    /** @var LoggerInterface */
    private $logger;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function configure(ConsoleCommandEvent $event): void
    {
        if ($this->logger && $this->isSupportedCommand($event->getCommand())) {
            $handler = set_exception_handler('var_dump');
            $handler = $handler[0] ?? null;

            restore_exception_handler();
            if ($handler instanceof ErrorHandler) {
                $handler->setDefaultLogger(
                    $this->logger,
                    E_USER_ERROR | E_RECOVERABLE_ERROR | E_COMPILE_ERROR | E_PARSE | E_ERROR | E_CORE_ERROR
                );
            }

            $this->logger = null;
        }
    }

    /**
     * @param Command $command
     * @return bool
     */
    private function isSupportedCommand(Command $command): bool
    {
        return $command instanceof ConsumeMessagesCommand || $command instanceof TransportConsumeMessagesCommand;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [
            // See \Symfony\Component\HttpKernel\EventListener\DebugHandlersListener::getSubscribedEvents.
            // this event listener should be executed next after Symfony's listener.
            ConsoleEvents::COMMAND => ['configure', 2047],
        ];
    }
}
