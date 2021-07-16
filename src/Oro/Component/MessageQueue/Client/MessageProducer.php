<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Exception\InvalidArgumentException;
use Oro\Component\MessageQueue\Exception\TopicSubscriberNotFoundException;
use Oro\Component\MessageQueue\Router\RecipientListRouterInterface;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * Message producer that used when messages are sending to the queue.
 * Prepares a message before sending, forward the message to all queues associated with the current topic.
 */
class MessageProducer implements MessageProducerInterface
{
    /** @var DriverInterface */
    private $driver;

    /** @var RecipientListRouterInterface */
    private $router;

    /** @var DestinationMetaRegistry */
    private $destinationMetaRegistry;

    public function __construct(
        DriverInterface $driver,
        RecipientListRouterInterface $router,
        DestinationMetaRegistry $destinationMetaRegistry
    ) {
        $this->driver = $driver;
        $this->router = $router;
        $this->destinationMetaRegistry = $destinationMetaRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message): void
    {
        $subscribers = $this->router->getTopicSubscribers($topic);
        if (!$subscribers) {
            throw new TopicSubscriberNotFoundException(
                sprintf('There is no message processors subscribed for topic "%s".', $topic)
            );
        }

        foreach ($subscribers as [$processorName, $queueName]) {
            $queueName = $this->destinationMetaRegistry->getDestinationMeta($queueName)->getTransportName();

            $message = $this->getMessage($message);
            $message->setProperty(Config::PARAMETER_TOPIC_NAME, $topic);
            $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, $processorName);
            $message->setProperty(Config::PARAMETER_QUEUE_NAME, $queueName);

            $queue = $this->driver->createQueue($queueName);
            $this->driver->send($queue, $message);
        }
    }

    /**
     * @param Message|MessageBuilderInterface|array|string|null $rawMessage
     *
     * @return Message
     */
    private function getMessage($rawMessage): Message
    {
        if ($rawMessage instanceof MessageBuilderInterface) {
            $rawMessage = $rawMessage->getMessage();
        }
        if ($rawMessage instanceof Message) {
            $message = clone $rawMessage;
        } else {
            $message = new Message();
            $message->setBody($rawMessage);
        }

        $message->setContentType($this->getContentType($message));
        $message->setBody($this->getBody($message));

        if (!$message->getMessageId()) {
            $message->setMessageId(uniqid('oro.', true));
        }
        if (!$message->getTimestamp()) {
            $message->setTimestamp(time());
        }
        if (!$message->getPriority()) {
            $message->setPriority(MessagePriority::NORMAL);
        }

        return $message;
    }

    private function getContentType(Message $message): string
    {
        $body = $message->getBody();
        $contentType = $message->getContentType();
        if (is_array($body)) {
            if ($contentType && $contentType !== 'application/json') {
                throw new InvalidArgumentException('When body is array content type must be "application/json".');
            }

            return 'application/json';
        }

        return $contentType ?: 'text/plain';
    }

    private function getBody(Message $message): string
    {
        $body = $message->getBody();

        if (null === $body || is_scalar($body)) {
            return (string) $body;
        }

        if (is_array($body)) {
            return JSON::encode($body);
        }

        throw new InvalidArgumentException(sprintf(
            'The message\'s body must be either null, scalar or array. Got: %s',
            is_object($body) ? get_class($body) : gettype($body)
        ));
    }
}
