<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Message;

class FrontProducer implements ProducerInterface
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Session $session
     * @param Config  $config
     */
    public function __construct(Session $session, Config $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function send(Message $message)
    {
        $messageName = $message->getProperty(Config::PARAMETER_MESSAGE_NAME);
        if (false == $messageName) {
            throw new \LogicException(sprintf('Got message without required parameter: "%s"', Config::PARAMETER_MESSAGE_NAME));
        }
        
        $transportSession = $this->session->getTransportSession();

        $topic = $this->session->createRouterTopic();
        $queue = $this->session->createRouterQueue();

        $transportSession->declareTopic($topic);
        $transportSession->declareQueue($queue);
        $transportSession->declareBind($topic, $queue);

        $transportSession->createProducer()->send($topic, $message);
    }
}
