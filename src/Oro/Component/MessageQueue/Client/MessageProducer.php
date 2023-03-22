<?php

namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Client\Router\MessageRouterInterface;
use Oro\Component\MessageQueue\Exception\InvalidArgumentException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Message producer that used when messages are sending to the queue.
 * Prepares a message before sending, forward the message to all queues associated with the current topic.
 */
class MessageProducer implements MessageProducerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private DriverInterface $driver;

    private MessageRouterInterface $messageRouter;

    private bool $debug;

    /** @var MessageProducerMiddlewareInterface[] */
    private iterable $middlewares = [];

    public function __construct(
        DriverInterface $driver,
        MessageRouterInterface $messageRouter,
        bool $debug = false
    ) {
        $this->driver = $driver;
        $this->messageRouter = $messageRouter;
        $this->debug = $debug;

        $this->logger = new NullLogger();
    }

    /**
     * @param MessageProducerMiddlewareInterface[] $middlewares
     * @return void
     */
    public function setMiddlewares(iterable $middlewares): void
    {
        $this->middlewares = $middlewares;
    }

    /**
     * {@inheritdoc}
     */
    public function send($topic, $message): void
    {
        try {
            $message = $this->createMessage($topic, $message);
            foreach ($this->middlewares as $middleware) {
                $middleware->handle($message);
            }

            foreach ($this->messageRouter->handle($message) as $envelope) {
                $this->driver->send($envelope->getQueue(), $envelope->getMessage());
            }
        } catch (\RuntimeException $exception) {
            $this->logger->error(
                sprintf(
                    'Failed to send the message with topic %s to message queue: %s',
                    $topic,
                    $exception->getMessage()
                ),
                ['exception' => $exception, 'topic' => $topic, 'message' => $message]
            );

            if ($this->debug) {
                throw $exception;
            }
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

        $message->setProperty(Config::PARAMETER_TOPIC_NAME, $topicName);

        return $message;
    }

    /**
     * @throws InvalidArgumentException
     */
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

    private function getBody(Message $message): string|array
    {
        $body = $message->getBody();

        if (null === $body || is_scalar($body)) {
            return (string)$body;
        }

        if (is_array($body)) {
            return $body;
        }

        throw new InvalidArgumentException(
            sprintf(
                'The message\'s body must be either null, scalar or array. Got: %s',
                get_debug_type($body)
            )
        );
    }
}
