<?php

namespace Oro\Bundle\MessageQueueBundle\Log\Handler;

use Monolog\Handler\BufferHandler as BaseBufferHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\HandlerWrapper;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use Monolog\LogRecord;
use Oro\Component\MessageQueue\Log\ConsumerState;

/**
 * Buffers all records until flush was not triggered and then pass them as batch.
 * Together with \Oro\Bundle\MessageQueueBundle\EventListener\ConsoleFatalErrorListener
 * write all logs on `console.error` event for the last queue message
 *
 * @property BaseBufferHandler $handler
 */
class ConsoleErrorHandler extends HandlerWrapper
{
    /** @var ConsumerState */
    private $consumerState;

    /**
     * @param ConsumerState $consumerState
     * @param HandlerInterface|null $handler
     * @param int $level
     */
    public function __construct(ConsumerState $consumerState, ?HandlerInterface $handler, $level = Logger::DEBUG)
    {
        parent::__construct($handler ? new BaseBufferHandler($handler, 0, $level) : new NullHandler());

        $this->consumerState = $consumerState;
    }

    #[\Override]
    public function isHandling(LogRecord $record): bool
    {
        return $this->consumerState->isConsumptionStarted() && parent::isHandling($record);
    }

    #[\Override]
    public function handle(LogRecord $record): bool
    {
        return $this->consumerState->isConsumptionStarted() && parent::handle($record);
    }

    public function clear()
    {
        $this->handler->clear();
    }

    #[\Override]
    public function reset(): void
    {
        // Clearing all buffered records because the BufferHandler flushes them to the output before resetting.
        $this->clear();

        parent::reset();
    }
}
