<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\DestinationInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface as TransportMessageProducer;

class MessageProducer implements TransportMessageProducer
{
    /**
     * @var TransportMessageProducer
     */
    protected $transportProducer;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @param TransportMessageProducer $transportProducer
     * @param SessionInterface                  $session
     */
    public function __construct(TransportMessageProducer $transportProducer, SessionInterface $session)
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
    public function send(DestinationInterface $destination, MessageInterface $message)
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
            if (isset($headers['content_type']) && $headers['content_type'] !== 'application/json') {
                throw new \LogicException(sprintf('Content type "application/json" only allowed when body is array'));
            }

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
