<?php

namespace Oro\Bundle\MessageQueueBundle\Log\Handler;

use Monolog\Handler\HandlerWrapper;
use Oro\Bundle\MessageQueueBundle\Log\Formatter\ConsoleFormatter;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler as BaseConsoleHandler;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Writes message queue consumer related logs to the console output depending on its verbosity setting.
 * It is disabled by default and gets activated as soon as ConsumerState::startConsumption is called.
 * @see \Oro\Component\MessageQueue\Log\ConsumerState::startConsumption
 *
 * @property BaseConsoleHandler $handler
 */
class ConsoleHandler extends HandlerWrapper implements EventSubscriberInterface
{
    /** @var ConsumerState */
    private $consumerState;

    /** @var int */
    private $commandNestedLevel = 0;

    public function __construct(ConsumerState $consumerState)
    {
        parent::__construct(new BaseConsoleHandler());

        $this->consumerState = $consumerState;
        $this->setFormatter(new ConsoleFormatter());
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record): bool
    {
        return
            $this->consumerState->isConsumptionStarted()
            && parent::isHandling($record);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record): bool
    {
        return
            $this->consumerState->isConsumptionStarted()
            && parent::handle($record);
    }

    /**
     * Before a command is executed, the handler gets activated and the console output
     * is set in order to know where to write the logs.
     */
    public function onCommand(ConsoleCommandEvent $event)
    {
        $this->commandNestedLevel++;
        if (1 === $this->commandNestedLevel) {
            // use stdout, not stderr as in Symfony, because this handler
            // is the main channel to write console messages for the consumer command
            $this->handler->setOutput($event->getOutput());
        }
    }

    /**
     * After a command has been executed, it disables the output.
     */
    public function onTerminate(ConsoleTerminateEvent $event)
    {
        $this->commandNestedLevel--;
        if (0 === $this->commandNestedLevel) {
            $this->handler->onTerminate($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return BaseConsoleHandler::getSubscribedEvents();
    }
}
