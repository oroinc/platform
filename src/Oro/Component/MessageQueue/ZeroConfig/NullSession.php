<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

use Oro\Component\MessageQueue\Transport\Null\NullSession as TransportNullSession;
use Oro\Component\MessageQueue\Transport\Queue;

class NullSession implements Session
{
    /**
     * @var TransportNullSession
     */
    protected $session;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param TransportNullSession $session
     * @param Config               $config
     */
    public function __construct(TransportNullSession $session, Config $config)
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
    public function createTransportMessageProducer()
    {
        return $this->session->createProducer();
    }

    /**
     * {@inheritdoc}
     */
    public function createMessageProducer()
    {
        return new MessageProducer($this, $this->config);
    }

    /**
     * @param string $queueName
     *
     * @return Queue
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
