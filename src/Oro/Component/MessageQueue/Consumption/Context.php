<?php
namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Consumption\Exception\IllegalContextModificationException;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\MessageConsumerInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

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
     * @var MessageProcessorInterface
     */
    private $messageProcessor;

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
     * @param SessionInterface $session
     */
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

    /**
     * @param MessageInterface $message
     */
    public function setMessage(MessageInterface $message)
    {
        if ($this->message) {
            throw new IllegalContextModificationException('The message modification is not allowed');
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

    /**
     * @param MessageConsumerInterface $messageConsumer
     */
    public function setMessageConsumer(MessageConsumerInterface $messageConsumer = null)
    {
        $this->messageConsumer = $messageConsumer;
    }

    /**
     * @return MessageProcessorInterface
     */
    public function getMessageProcessor()
    {
        return $this->messageProcessor;
    }

    /**
     * @param MessageProcessorInterface $messageProcessor
     */
    public function setMessageProcessor(MessageProcessorInterface $messageProcessor = null)
    {
        $this->messageProcessor = $messageProcessor;
    }

    /**
     * @return \Exception
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * @param \Exception $exception
     */
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

    /**
     * @param LoggerInterface $logger
     */
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
