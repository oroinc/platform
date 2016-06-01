<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Transport\QueueInterface;

class NullDriver implements DriverInterface
{
    /**
     * @var NullSession
     */
    protected $session;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param NullSession $session
     * @param Config               $config
     */
    public function __construct(NullSession $session, Config $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function createMessage()
    {
        return $this->session->createMessage();
    }

    /**
     * {@inheritdoc}
     */
    public function setMessagePriority(MessageInterface $message, $priority)
    {
        $headers = $message->getHeaders();
        $headers['priority'] = $priority;
        $message->setHeaders($headers);
    }

    /**
     * {@inheritdoc}
     */
    public function createProducer()
    {
        return new MessageProducer($this->session->createProducer(), $this);
    }

    /**
     * @param string $queueName
     *
     * @return QueueInterface
     */
    public function createQueue($queueName)
    {
        return $this->session->createQueue($queueName);
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }
}
