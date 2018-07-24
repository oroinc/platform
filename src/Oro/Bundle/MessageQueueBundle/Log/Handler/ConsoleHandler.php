<?php

namespace Oro\Bundle\MessageQueueBundle\Log\Handler;

use Oro\Bundle\MessageQueueBundle\Log\Formatter\ConsoleFormatter;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler as BaseConsoleHandler;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;

/**
 * Writes message queue consumer related logs to the console output depending on its verbosity setting.
 * It is disabled by default and gets activated as soon as ConsumerState::startConsumption is called.
 * @see \Oro\Component\MessageQueue\Log\ConsumerState::startConsumption
 */
class ConsoleHandler extends BaseConsoleHandler
{
    /** @var ConsumerState */
    private $consumerState;

    /** @var int */
    private $commandNestedLevel = 0;

    /**
     * @param ConsumerState $consumerState     The object that stores the current state of message queue consumer
     * @param bool          $bubble            Whether the messages that are handled can bubble up the stack or not
     * @param array         $verbosityLevelMap Array that maps the OutputInterface verbosity to a minimum logging
     *                                         level (leave empty to use the default mapping)
     */
    public function __construct(ConsumerState $consumerState, $bubble = true, array $verbosityLevelMap = [])
    {
        parent::__construct(null, $bubble, $verbosityLevelMap);
        $this->consumerState = $consumerState;
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record)
    {
        return
            $this->consumerState->isConsumptionStarted()
            && parent::isHandling($record);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record)
    {
        if (!$this->consumerState->isConsumptionStarted()) {
            return false;
        }

        return parent::handle($record);
    }

    /**
     * {@inheritdoc}
     */
    public function onCommand(ConsoleCommandEvent $event)
    {
        $this->commandNestedLevel++;
        if (1 === $this->commandNestedLevel) {
            // use stdout, not stderr as in Symfony, because this handler
            // is the main channel to write console messages for the consumer command
            $this->setOutput($event->getOutput());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onTerminate(ConsoleTerminateEvent $event)
    {
        $this->commandNestedLevel--;
        if (0 === $this->commandNestedLevel) {
            parent::onTerminate($event);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultFormatter()
    {
        return new ConsoleFormatter();
    }
}
