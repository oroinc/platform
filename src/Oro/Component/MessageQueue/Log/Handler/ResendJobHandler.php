<?php

namespace Oro\Component\MessageQueue\Log\Handler;

use Monolog\Logger;
use Oro\Component\MessageQueue\Log\ConsumerState;
use Psr\Log\LoggerInterface;

/**
 * Resends a message queue job related log record to the special logger.
 */
class ResendJobHandler extends AbstractResendHandler
{
    /** @var ConsumerState */
    private $consumerState;

    /**
     * @param LoggerInterface $jobLogger     The logger for message queue job related records
     * @param ConsumerState   $consumerState The object that stores the current state of message queue consumer
     * @param int             $level         The minimum logging level at which this handler will be triggered
     * @param bool            $bubble        Whether the messages that are handled can bubble up the stack or not
     */
    public function __construct(
        LoggerInterface $jobLogger,
        ConsumerState $consumerState,
        $level = Logger::DEBUG,
        $bubble = true
    ) {
        parent::__construct($jobLogger, $level, $bubble);
        $this->consumerState = $consumerState;
    }

    /**
     * {@inheritdoc}
     */
    protected function isResendRequired(array $record)
    {
        return null !== $this->consumerState->getJob();
    }
}
