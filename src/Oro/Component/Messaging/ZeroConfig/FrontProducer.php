<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Message;

class FrontProducer implements ProducerInterface
{
    /**
     * @var Session
     */
    private $session;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Session $transportFactory
     * @param Config $config
     */
    public function __construct(Session $transportFactory, Config $config)
    {
        $this->session = $transportFactory;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Message $message)
    {
        $messageName = $message->getProperty('messageName');
        if (false == $messageName) {
            throw new \LogicException('Got message without "messageName" parameter');
        }
        
        $transportSession = $this->session->getTransportSession();
        try {
            $topic = $this->session->createFrontTopic();
            $queue = $this->session->createFrontQueue();

            $transportSession->declareTopic($topic);
            $transportSession->declareQueue($topic);
            $transportSession->declareBind($topic, $queue);

            $transportSession->createProducer()->send($topic, $message);
        } finally {
            $transportSession->close();
        }
    }
}
