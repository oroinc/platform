<?php
namespace Oro\Component\MessageQueue\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Destination;
use Oro\Component\MessageQueue\Transport\Exception\InvalidDestinationException;
use Oro\Component\MessageQueue\Transport\Session;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpSession implements Session
{
    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @param AMQPChannel $channel
     */
    public function __construct(AMQPChannel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * @internal
     *
     * @return AMQPChannel
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpMessage
     */
    public function createMessage($body = null, array $properties = [], array $headers = [])
    {
        $message = new AmqpMessage();
        $message->setBody($body);
        $message->setProperties($properties);
        $message->setHeaders($headers);

        return $message;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpQueue
     */
    public function createQueue($name)
    {
        return new AmqpQueue($name);
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpTopic
     */
    public function createTopic($name)
    {
        return new AmqpTopic($name);
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpQueue $destination
     *
     * @return AmqpMessageConsumer
     */
    public function createConsumer(Destination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpQueue::class);
        
        return new AmqpMessageConsumer($this, $destination);
    }
    
    /**
     * {@inheritdoc}
     *
     * @return AmqpMessageProducer
     */
    public function createProducer()
    {
        return new AmqpMessageProducer($this->channel);
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpTopic $destination
     */
    public function declareTopic(Destination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpTopic::class);

        $this->channel->exchange_declare(
            $destination->getTopicName(),
            $destination->getType(),
            $destination->isPassive(),
            $destination->isDurable(),
            $autoDelete = false, // rabbitmq specific
            $internal = false, // rabbitmq specific
            $destination->isNoWait(),
            $destination->getTable() ? new AMQPTable($destination->getTable()) : null
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpQueue $destination
     */
    public function declareQueue(Destination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpQueue::class);

        $this->channel->queue_declare(
            $destination->getQueueName(),
            $destination->isPassive(),
            $destination->isDurable(),
            $destination->isExclusive(),
            $destination->isAutoDelete(),
            $destination->isNoWait(),
            $destination->getTable() ? new AMQPTable($destination->getTable()) : null
        );
    }

    /**
     * {@inheritdoc}
     *
     * @param AmqpTopic $source
     * @param AmqpQueue $target
     */
    public function declareBind(Destination $source, Destination $target)
    {
        InvalidDestinationException::assertDestinationInstanceOf($source, AmqpTopic::class);
        InvalidDestinationException::assertDestinationInstanceOf($target, AmqpQueue::class);
        
        $this->channel->queue_bind($target->getQueueName(), $source->getTopicName(), $source->getRoutingKey());
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->channel->close();
    }
}
