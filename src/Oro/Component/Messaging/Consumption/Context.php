<?php
namespace Oro\Component\Messaging\Consumption;

use Oro\Component\Messaging\Consumption\Exception\IllegalContextModificationException;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\MessageConsumer;
use Oro\Component\Messaging\Transport\Session;

class Context
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var MessageProcessor
     */
    private $messageConsumer;

    /**
     * @var MessageProcessor
     */
    private $messageProcessor;

    /**
     * @var Message
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
     * @var boolean
     */
    private $executionInterrupted;

    /**
     * @param Session $session
     * @param MessageConsumer $messageConsumer
     * @param MessageProcessor $messageProcessor
     */
    public function __construct(Session $session, MessageConsumer $messageConsumer, MessageProcessor $messageProcessor)
    {
        $this->session = $session;
        $this->messageConsumer = $messageConsumer;
        $this->messageProcessor = $messageProcessor;
        $this->executionInterrupted = false;
    }

    /**
     * @return Message
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param Message $message
     */
    public function setMessage(Message $message)
    {
        if ($this->message) {
            throw new IllegalContextModificationException('The message modification is not allowed');
        }

        $this->message = $message;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @return MessageProcessor
     */
    public function getMessageConsumer()
    {
        return $this->messageConsumer;
    }

    /**
     * @return MessageProcessor
     */
    public function getMessageProcessor()
    {
        return $this->messageProcessor;
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
        if ($this->executionInterrupted) {
            throw new IllegalContextModificationException('The execution once interrupted could not be roll backed');
        }

        $this->executionInterrupted = $executionInterrupted;
    }
}
