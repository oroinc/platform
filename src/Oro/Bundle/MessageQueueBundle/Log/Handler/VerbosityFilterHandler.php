<?php

namespace Oro\Bundle\MessageQueueBundle\Log\Handler;

use Monolog\Handler\FilterHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\HandlerWrapper;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Filter message queue consumer related logs depending on its verbosity setting.
 * It is disabled by default and gets activated as soon as ConsumerState::startConsumption is called.
 * @see \Oro\Component\MessageQueue\Log\ConsumerState::startConsumption
 *
 * @property FilterHandler $handler
 */
class VerbosityFilterHandler extends HandlerWrapper implements EventSubscriberInterface
{
    /** @var ConsumerState */
    private $consumerState;

    /** @var OutputInterface */
    private $output;

    /** @var array */
    private $verbosityLevelMap = [
        OutputInterface::VERBOSITY_QUIET => Logger::ERROR,
        OutputInterface::VERBOSITY_NORMAL => Logger::WARNING,
        OutputInterface::VERBOSITY_VERBOSE => Logger::NOTICE,
        OutputInterface::VERBOSITY_VERY_VERBOSE => Logger::INFO,
        OutputInterface::VERBOSITY_DEBUG => Logger::DEBUG,
    ];

    /**
     * @param ConsumerState $consumerState
     * @param HandlerInterface $handler
     * @param array $verbosityLevelMap
     */
    public function __construct(ConsumerState $consumerState, ?HandlerInterface $handler, array $verbosityLevelMap = [])
    {
        parent::__construct($handler ? new FilterHandler($handler) : new NullHandler());

        $this->consumerState = $consumerState;

        if ($verbosityLevelMap) {
            $this->verbosityLevelMap = $verbosityLevelMap;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record): bool
    {
        return
            $this->consumerState->isConsumptionStarted()
            && $this->updateLevel()
            && parent::isHandling($record);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record): bool
    {
        return
            $this->consumerState->isConsumptionStarted()
            && $this->updateLevel()
            && parent::handle($record);
    }

    public function onCommand(ConsoleCommandEvent $event)
    {
        $this->output = $event->getOutput();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ConsoleEvents::COMMAND => ['onCommand', 255]
        ];
    }

    /**
     * @return bool
     */
    private function updateLevel()
    {
        if (null === $this->output) {
            return false;
        }

        $verbosity = $this->output->getVerbosity();

        if (array_key_exists($verbosity, $this->verbosityLevelMap)) {
            $this->handler->setAcceptedLevels($this->verbosityLevelMap[$verbosity]);
        }

        return true;
    }
}
