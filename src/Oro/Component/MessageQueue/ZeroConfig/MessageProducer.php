<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

use Oro\Component\MessageQueue\Transport\Destination;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageProducer as TransportMessageProducer;

class MessageProducer implements TransportMessageProducer
{
    /**
     * @var TransportMessageProducer
     */
    protected $messageProducer;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param TransportMessageProducer $messageProducer
     * @param Session                  $session
     */
    public function __construct(TransportMessageProducer $messageProducer, Session $session)
    {
        $this->messageProducer = $messageProducer;
        $this->session = $session;
    }

    /**
     * @param string $topic
     * @param string $body
     */
    public function sendTo($topic, $body)
    {
        if (is_scalar($body) || is_null($body)) {
            $contentType = 'text/plain';
            $body = (string) $body;
        } elseif (is_array($body)) {
            $contentType = 'application/json';
            $body = json_encode($body);
        } else {
            throw new \InvalidArgumentException(sprintf(
                'The message\'s body must be either null, scalar or array. Got: %s',
                is_object($body) ? get_class($body) : gettype($body)
            ));
        }
        
        $config = $this->session->getConfig();

        $message = $this->session->createMessage();

        $message->setBody($body);
        
        $headers = $message->getHeaders();
        $headers['content_type'] = $contentType;
        $message->setHeaders($headers);

        $properties = $message->getProperties();
        $properties[Config::PARAMETER_TOPIC_NAME] = $topic;
        $properties[Config::PARAMETER_PROCESSOR_NAME] = $config->getRouterMessageProcessorName();
        $properties[Config::PARAMETER_QUEUE_NAME] = $config->getRouterQueueName();
        $message->setProperties($properties);

        $queue = $this->session->createQueue($config->getRouterQueueName());
        
        $this->send($queue, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function send(Destination $destination, Message $message)
    {
        $this->messageProducer->send($destination, $message);
    }
}
