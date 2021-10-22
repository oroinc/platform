<?php

namespace Oro\Bundle\LoggerBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\ErrorHandler\ErrorHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Configures default logger when error was occurred.
 */
class ConsoleErrorSubscriber implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function configure(ConsoleCommandEvent $event): void
    {
        if ($this->logger && $event->getCommand()) {
            $handler = set_exception_handler('var_dump');
            $handler = $handler[0] ?? null;

            restore_exception_handler();
            if ($handler instanceof ErrorHandler) {
                $handler->setDefaultLogger(
                    $this->logger,
                    E_USER_ERROR | E_RECOVERABLE_ERROR | E_COMPILE_ERROR | E_PARSE | E_ERROR | E_CORE_ERROR
                );
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents(): array
    {
        return [ConsoleEvents::COMMAND => ['configure', 2048]];
    }
}
