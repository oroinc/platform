<?php

namespace Oro\Component\MessageQueue\Log\Handler;

use Monolog\Handler\AbstractHandler;
use Monolog\Logger;
use Oro\Component\MessageQueue\Log\Processor\RestoreOriginalChannelProcessor;
use Psr\Log\LoggerInterface;

/**
 * A base handler to resend log records to another log channel.
 * The original log channel is stored in the record context under the "log_channel" key.
 */
abstract class AbstractResendHandler extends AbstractHandler
{
    /** @var LoggerInterface */
    private $targetLogger;

    /**
     * @param LoggerInterface $targetLogger The logger to resent log records
     * @param int             $level        The minimum logging level at which this handler will be triggered
     * @param bool            $bubble       Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(LoggerInterface $targetLogger, $level = Logger::DEBUG, $bubble = true)
    {
        parent::__construct($level, $bubble);
        $this->targetLogger = $targetLogger;
    }

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record)
    {
        return $this->isResendRequired($record);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(array $record)
    {
        if ($this->isResendRequired($record)) {
            /**
             * store the original channel to the context to be able to write the original channel to the log
             * @see \Oro\Component\MessageQueue\Log\Processor\RestoreOriginalChannelProcessor
             */
            $context = $record[RestoreOriginalChannelProcessor::CONTEXT_KEY];
            $context[RestoreOriginalChannelProcessor::LOG_CHANNEL_KEY] = $record['channel'];

            $this->targetLogger->log($record['level'], $record['message'], $context);
        }

        return false === $this->bubble;
    }

    /**
     * Indicates whether the record should be resent to the target logger.
     *
     * @param array $record The log record to handle
     *
     * @return bool
     */
    abstract protected function isResendRequired(array $record);
}
