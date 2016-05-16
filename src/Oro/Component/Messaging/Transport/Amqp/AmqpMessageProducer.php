<?php
namespace Oro\Component\Messaging\Transport\Amqp;

use Oro\Component\Messaging\Transport\Destination;
use Oro\Component\Messaging\Transport\Exception\InvalidDestinationException;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\MessageProducer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;
use PhpAmqpLib\Wire\AMQPTable;

final class AmqpMessageProducer implements MessageProducer
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
     * {@inheritdoc}
     *
     * @param AmqpTopic $destination
     *
     * @return void
     */
    public function send(Destination $destination, Message $message)
    {
        InvalidDestinationException::assertDestinationInstanceOf(
            $destination,
            'Oro\Component\Messaging\Transport\Amqp\AmqpTopic'
        );
        
        $amqpMessage = new AMQPLibMessage($message->getBody(), $message->getHeaders());
        $amqpMessage->set('application_headers', new AMQPTable($message->getProperties()));

        $this->channel->basic_publish(
            $amqpMessage,
            $destination->getTopicName(),
            $destination->getRoutingKey(),
            $destination->isMandatory(),
            $destination->isImmediate()
        );
    }
}
