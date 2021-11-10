<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Client\Router\MessageRouterInterface;
use Oro\Component\MessageQueue\Exception\InvalidArgumentException;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * Message producer that used when messages are sending to the queue.
 * Prepares a message before sending, forward the message to all queues associated with the current topic.
 */
class MessageProducer implements MessageProducerInterface
{
    private DriverInterface $driver;

    private MessageRouterInterface $messageRouter;

    public function __construct(DriverInterface $driver, MessageRouterInterface $messageRouter)
    {
        $this->driver = $driver;
        $this->messageRouter = $messageRouter;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message): void
    {
        $message = $this->createMessage($topic, $message);
        foreach ($this->messageRouter->handle($message) as $envelope) {
            $this->driver->send($envelope->getQueue(), $envelope->getMessage());
        }
    }

    /**
     * @param string $topicName
     * @param Message|MessageBuilderInterface|array|string|null $rawMessage
     *
     * @return Message
     */
    private function createMessage(string $topicName, mixed $rawMessage): Message
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

        $message->setProperty(Config::PARAMETER_TOPIC_NAME, $topicName);

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
            return (string)$body;
        }

        if (is_array($body)) {
            return JSON::encode($body);
        }

        throw new InvalidArgumentException(
            sprintf(
                'The message\'s body must be either null, scalar or array. Got: %s',
                get_debug_type($body)
            )
        );
    }
}
