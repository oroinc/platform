<?php
namespace Oro\Component\MessageQueue\Transport\Null;

use Oro\Component\MessageQueue\Transport\DestinationInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

class NullSession implements SessionInterface
{
    /**
     * {@inheritdoc}
     *
     * @return NullMessage
     */
    public function createMessage($body = null, array $properties = [], array $headers = [])
    {
        $message = new NullMessage();
        $message->setBody($body);
        $message->setProperties($properties);
        $message->setHeaders($headers);

        return $message;
    }

    /**
     * {@inheritdoc}
     *
     * @return NullQueue
     */
    public function createQueue($name)
    {
        return new NullQueue($name);
    }

    /**
     * {@inheritdoc}
     *
     * @return NullTopic
     */
    public function createTopic($name)
    {
        return new NullTopic($name);
    }

    /**
     * {@inheritdoc}
     *
     * @return NullMessageConsumer
     */
    public function createConsumer(DestinationInterface $destination)
    {
        return new NullMessageConsumer($destination);
    }
    
    /**
     * {@inheritdoc}
     */
    public function createProducer()
    {
        return new NullMessageProducer();
    }

    /**
     * {@inheritdoc}
     */
    public function declareTopic(DestinationInterface $destination)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function declareQueue(DestinationInterface $destination)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function declareBind(DestinationInterface $source, DestinationInterface $target)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
    }
}
