<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\DestinationInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface as TransportMessageProducer;
use Oro\Component\MessageQueue\Util\JSON;

class MessageProducer implements MessageProducerInterface
{
    /**
     * @var TransportMessageProducer
     */
    protected $transportProducer;

    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @param TransportMessageProducer $transportProducer
     * @param DriverInterface                  $driver
     */
    public function __construct(TransportMessageProducer $transportProducer, DriverInterface $driver)
    {
        $this->transportProducer = $transportProducer;
        $this->driver = $driver;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message, $priority = MessagePriority::NORMAL)
    {
        $config = $this->driver->getConfig();

        $transportMessage = $this->driver->createMessage();
        $this->driver->setMessagePriority($transportMessage, $priority);

        $transportMessage->setBody($message);

        $properties = $transportMessage->getProperties();
        $properties[Config::PARAMETER_TOPIC_NAME] = $topic;
        $properties[Config::PARAMETER_PROCESSOR_NAME] = $config->getRouterMessageProcessorName();
        $properties[Config::PARAMETER_QUEUE_NAME] = $config->getRouterQueueName();
        $transportMessage->setProperties($properties);
        
        $queue = $this->driver->createQueue($config->getRouterQueueName());
        
        $this->sendMessage($queue, $transportMessage);
    }

    /**
     * {@inheritdoc}
     */
    protected function sendMessage(DestinationInterface $destination, MessageInterface $message)
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
            $body = JSON::encode($body);
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
