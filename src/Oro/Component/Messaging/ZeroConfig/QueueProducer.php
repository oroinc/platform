<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Amqp\AmqpQueue;
use Oro\Component\Messaging\Transport\Amqp\AmqpSession as TransportAmqpSession;
use Oro\Component\Messaging\Transport\Amqp\AmqpTopic;
use Oro\Component\Messaging\Transport\Message;

class QueueProducer implements ProducerInterface
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
     * @param Session $session
     * @param Config $config
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
        $processorName = $message->getProperty('processorName');
        if (false == $processorName) {
            throw new \LogicException('Got message without "processorName" parameter');
        }

        $transportSession = $this->session->getTransportSession();
        try {
            $queueName = $message->getProperty('queueName')
                ? $this->config->formatName($message->getProperty('queueName'))
                : $this->config->getDefaultQueueQueueName()
            ;

            $topic = $this->session->createQueueTopic($queueName);
            $queue = $this->session->createQueueQueue($queueName);
            
            $transportSession->declareTopic($topic);
            $transportSession->declareQueue($topic);
            $transportSession->declareBind($topic, $queue);

            $transportSession->createProducer()->send($topic, $message);
        } finally {
            $transportSession->close();
        }
    }
}
