<?php
namespace Oro\Component\MessageQueue\Transport\Amqp;

use Oro\Component\MessageQueue\Transport\Destination;
use Oro\Component\MessageQueue\Transport\Exception\InvalidDestinationException;
use Oro\Component\MessageQueue\Transport\Exception\InvalidMessageException;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageProducer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage as AMQPLibMessage;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpMessageProducer implements MessageProducer
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
     */
    public function send(Destination $destination, Message $message)
    {
        $body = $message->getBody();
        if (is_scalar($body) || is_null($body)) {
            $body = (string)$body;
        } else {
            throw new InvalidMessageException(sprintf(
                'The message body must be a scalar or null. Got: %s',
                is_object($body) ? get_class($body) : gettype($body)
            ));
        }

        $amqpMessage = new AMQPLibMessage($body, $message->getHeaders());
        $amqpMessage->set('application_headers', new AMQPTable($message->getProperties()));

        if ($destination instanceof  AmqpTopic) {
            $this->channel->basic_publish(
                $amqpMessage,
                $destination->getTopicName(),
                $destination->getRoutingKey(),
                $destination->isMandatory(),
                $destination->isImmediate()
            );
        } elseif ($destination instanceof AmqpQueue) {
            $this->channel->basic_publish($amqpMessage, '', $destination->getQueueName());
        } else {
            InvalidDestinationException::assertDestinationInstanceOf(
                $destination,
                AmqpTopic::class.' or '.AmqpQueue::class
            );
        }
    }
}
