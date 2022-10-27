<?php

namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Consumption\Exception\IllegalContextModificationException;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

/**
 * Holds information about message consuming.
 */
class Context
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var MessageConsumerInterface
     */
    private $messageConsumer;

    /**
     * @var string
     */
    private string $messageProcessorName = '';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var MessageInterface
     */
    private $message;

    /**
     * @var \Exception
     */
    private $exception;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $queueName;

    /**
     * @var boolean
     */
    private $executionInterrupted;

    /**
     * @var boolean
     */
    private $interruptedReason;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;

        $this->executionInterrupted = false;
    }

    /**
     * @return MessageInterface
     */
    public function getMessage()
    {
        return $this->message;
    }

    public function setMessage(MessageInterface $message)
    {
        if ($this->message) {
            throw new IllegalContextModificationException('The message could be set once');
        }

        $this->message = $message;
    }

    /**
     * @return SessionInterface
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return MessageConsumerInterface
     */
    public function getMessageConsumer()
    {
        return $this->messageConsumer;
    }

    public function setMessageConsumer(MessageConsumerInterface $messageConsumer)
    {
        if ($this->messageConsumer) {
            throw new IllegalContextModificationException('The message consumer could be set once');
        }

        $this->messageConsumer = $messageConsumer;
    }

    public function getMessageProcessorName(): string
    {
        return $this->messageProcessorName;
    }

    public function setMessageProcessorName(string $messageProcessorName): void
    {
        $this->messageProcessorName = $messageProcessorName;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    public function setException(\Exception $exception)
    {
        $this->exception = $exception;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        if ($this->status) {
            throw new IllegalContextModificationException('The status modification is not allowed');
        }

        $this->status = $status;
    }

    /**
     * @return boolean
     */
    public function isExecutionInterrupted()
    {
        return $this->executionInterrupted;
    }

    /**
     * @return string
     */
    public function getInterruptedReason()
    {
        return $this->interruptedReason;
    }

    /**
     * @param string $reason
     */
    public function setInterruptedReason($reason)
    {
        $this->interruptedReason = $reason;
    }

    /**
     * @param boolean $executionInterrupted
     */
    public function setExecutionInterrupted($executionInterrupted)
    {
        if (false == $executionInterrupted && $this->executionInterrupted) {
            throw new IllegalContextModificationException('The execution once interrupted could not be roll backed');
        }

        $this->executionInterrupted = $executionInterrupted;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public function setLogger(LoggerInterface $logger)
    {
        if ($this->logger) {
            throw new IllegalContextModificationException('The logger modification is not allowed');
        }

        $this->logger = $logger;
    }

    /**
     * @return string
     */
    public function getQueueName()
    {
        return $this->queueName;
    }

    /**
     * @param string $queueName
     */
    public function setQueueName($queueName)
    {
        if ($this->queueName) {
            throw new IllegalContextModificationException('The queueName modification is not allowed');
        }

        $this->queueName = $queueName;
    }
}
