<?php
namespace Oro\Component\Messaging\ZeroConfig\Amqp;

use Oro\Component\Messaging\Transport\Amqp\AmqpMessage;
use \Oro\Component\Messaging\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\Messaging\ZeroConfig\Config;
use Oro\Component\Messaging\ZeroConfig\SessionInterface;

class AmqpSession implements SessionInterface
{
    /**
     * @var TransportAmqpSession
     */
    protected $session;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param TransportAmqpSession $session
     * @param Config               $config
     */
    public function __construct(TransportAmqpSession $session, Config $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function createMessage()
    {
        return $this->session->createMessage(null, [], [
            'delivery_mode' => AmqpMessage::DELIVERY_MODE_PERSISTENT,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function createFrontProducer()
    {
        return new AmqpFrontProducer($this->session, $this->config);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueueProducer()
    {
        return new AmqpQueueProducer($this->session, $this->config);
    }
}
