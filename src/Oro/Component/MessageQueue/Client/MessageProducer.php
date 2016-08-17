<?php
namespace Oro\Component\MessageQueue\Client;

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

        if (false == $message instanceof MessageInterface) {
            $message = $this->createMessage($message);
        }

        $this->driver->setMessagePriority($message, $priority);

        $properties = $message->getProperties();
        $properties[Config::PARAMETER_TOPIC_NAME] = $topic;
        $properties[Config::PARAMETER_PROCESSOR_NAME] = $config->getRouterMessageProcessorName();
        $properties[Config::PARAMETER_QUEUE_NAME] = $config->getRouterQueueName();
        $message->setProperties($properties);
        
        $queue = $this->driver->createQueue($config->getRouterQueueName());

        $this->transportProducer->send($queue, $message);
    }

    /**
     * {@inheritdoc}
     */
    public function createMessage($body)
    {
        $message = $this->driver->createMessage();
        $headers = $message->getHeaders();

        if (is_scalar($body) || is_null($body)) {
            $headers['content_type'] = empty($headers['content_type']) ? 'text/plain' : $headers['content_type'];
            $body = (string) $body;
        } elseif (is_array($body)) {
            if (isset($headers['content_type']) && $headers['content_type'] !== 'application/json') {
                throw new \LogicException(sprintf('Content type "application/json" only allowed when body is array'));
            }

            // only array of scalars is allowed.
            array_walk_recursive($body, function ($value) {
                if (false == (is_scalar($value) || is_null($value))) {
                    throw new \LogicException(sprintf(
                        'The message\'s body must be an array of scalars. Got: %s',
                        is_object($value) ? get_class($value) : gettype($value)
                    ));
                }
            });

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

        $message->setMessageId(uniqid('oro', true));
        $message->setTimestamp(time());

        return $message;
    }
}
