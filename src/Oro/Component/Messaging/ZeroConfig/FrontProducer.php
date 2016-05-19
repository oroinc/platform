<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Message;

class FrontProducer
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
     * @param string $topic
     * @param string $body
     */
    public function send($topic, $body)
    {
        $message = $this->session->createMessage();
        $message->setBody($body);

        $properties = $message->getProperties();
        $properties[Config::PARAMETER_MESSAGE_NAME] = $topic;
        $message->setProperties($properties);

        $transportSession = $this->session->getTransportSession();

        $topic = $this->session->createRouterTopic();
        $queue = $this->session->createRouterQueue();

        $transportSession->declareTopic($topic);
        $transportSession->declareQueue($queue);
        $transportSession->declareBind($topic, $queue);

        $transportSession->createProducer()->send($topic, $message);
    }
}
