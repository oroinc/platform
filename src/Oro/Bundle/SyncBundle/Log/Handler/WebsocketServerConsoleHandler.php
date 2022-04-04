<?php

namespace Oro\Bundle\SyncBundle\Log\Handler;

use Gos\Bundle\WebSocketBundle\Command\WebsocketServerCommand;
use Monolog\Handler\HandlerWrapper;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler as SymfonyConsoleHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Enables/disables the monolog handler responsible for logging in console as soon as websocket server starts.
 */
class WebsocketServerConsoleHandler extends HandlerWrapper implements EventSubscriberInterface
{
    private int $commandNestedLevel = 0;

    public function __construct(SymfonyConsoleHandler $consoleHandler)
    {
        parent::__construct($consoleHandler);
    }

    /**
     * Before a command is executed, the handler gets activated and the console output
     * is set in order to know where to write the logs.
     */
    public function onCommand(ConsoleCommandEvent $event): void
    {
        $this->commandNestedLevel++;
        if (1 === $this->commandNestedLevel && $this->isApplicable($event->getCommand())) {
            $this->handler->setOutput($event->getOutput());
        }
    }

    /**
     * After a command has been executed, it disables the output.
     */
    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        $this->commandNestedLevel--;
        if (0 === $this->commandNestedLevel && $this->isApplicable($event->getCommand())) {
            $this->handler->onTerminate($event);
        }
    }

    private function isApplicable(?Command $command): bool
    {
        return $command && $command->getName() === WebsocketServerCommand::getDefaultName();
    }

    public static function getSubscribedEvents(): array
    {
        return SymfonyConsoleHandler::getSubscribedEvents();
    }
}
