<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Message;

class QueueProducer implements ProducerInterface
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
        $processorName = $message->getProperty(Config::PARAMETER_PROCESSOR_NAME);
        if (false == $processorName) {
            throw new \LogicException(sprintf('Got message without required parameter: "%s"', Config::PARAMETER_PROCESSOR_NAME));
        }

        $transportSession = $this->session->getTransportSession();

        $queueName = $message->getProperty(Config::PARAMETER_QUEUE_NAME)
            ? $this->config->formatName($message->getProperty(Config::PARAMETER_QUEUE_NAME))
            : $this->config->getDefaultQueueQueueName()
        ;

        $topic = $this->session->createQueueTopic($queueName);
        $queue = $this->session->createQueueQueue($queueName);

        $transportSession->declareTopic($topic);
        $transportSession->declareQueue($queue);
        $transportSession->declareBind($topic, $queue);

        $transportSession->createProducer()->send($topic, $message);
    }
}
