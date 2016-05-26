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
    protected $transportProducer;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @param TransportMessageProducer $transportProducer
     * @param Session                  $session
     */
    public function __construct(TransportMessageProducer $transportProducer, Session $session)
    {
        $this->transportProducer = $transportProducer;
        $this->session = $session;
    }

    /**
     * @param string $topic
     * @param string $body
     */
    public function sendTo($topic, $body)
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
        
        $this->send($queue, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function send(Destination $destination, Message $message)
    {
        if (false == $message->getProperty(Config::PARAMETER_TOPIC_NAME)) {
            throw new \LogicException(sprintf('Parameter "%s" is required.', Config::PARAMETER_TOPIC_NAME));
        }

        if (false == $message->getProperty(Config::PARAMETER_PROCESSOR_NAME)) {
            throw new \LogicException(sprintf('Parameter "%s" is required.', Config::PARAMETER_PROCESSOR_NAME));
        }

        $body = $message->getBody();
        $headers = $message->getHeaders();

        if (is_scalar($body) || is_null($body)) {
            $headers['content_type'] = empty($headers['content_type']) ? 'text/plain' : $headers['content_type'];
            $body = (string) $body;
        } elseif (is_array($body)) {
            $headers['content_type'] = 'application/json';
            $body = json_encode($body);
        } else {
            throw new \InvalidArgumentException(sprintf(
                'The message\'s body must be either null, scalar or array. Got: %s',
                is_object($body) ? get_class($body) : gettype($body)
            ));
        }

        $message->setHeaders($headers);
        $message->setBody($body);

        $this->transportProducer->send($destination, $message);
    }
}
