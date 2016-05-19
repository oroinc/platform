<?php
namespace Oro\Component\Messaging\ZeroConfig;

class FrontProducer
{
    /**
     * @var Session
     */
    protected $session;

    /**
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * @param string $topic
     * @param string $body
     */
    public function send($topic, $body)
    {
        $config = $this->session->getConfig();

        $message = $this->session->createMessage();

        $message->setBody($body);

        $properties = $message->getProperties();
        $properties[Config::PARAMETER_TOPIC_NAME] = $topic;
        $properties[Config::PARAMETER_PROCESSOR_NAME] = $config->getRouterMessageProcessorName();
        $properties[Config::PARAMETER_QUEUE_NAME] = $config->getRouterQueueName();
        $message->setProperties($properties);

        $queue = $this->session->createQueue($config->getRouterQueueName());
        
        $this->session->createProducer()->send($queue, $message);
    }
}
