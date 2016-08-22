<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\Exception\InvalidDestinationException;
use Oro\Component\MessageQueue\Transport\Null\NullQueue;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Transport\QueueInterface;

class NullDriver implements DriverInterface
{
    /**
     * @var NullSession
     */
    protected $session;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param NullSession $session
     * @param Config               $config
     */
    public function __construct(NullSession $session, Config $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName)
    {
        return $this->session->createQueue($queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function send(QueueInterface $queue, Message $message)
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, NullQueue::class);

        $destination = $queue;

        $headers = $message->getHeaders();
        $headers['content_type'] = $message->getContentType();
        $headers['expiration'] = $message->getExpireSec();
        $headers['delay'] = $message->getDelaySec();
        $headers['priority'] = $message->getPriority();

        $transportMessage = $this->session->createMessage();
        $transportMessage->setBody($message->getBody());
        $transportMessage->setProperties($message->getProperties());
        $transportMessage->setMessageId($message->getMessageId());
        $transportMessage->setTimestamp($message->getTimestamp());
        $transportMessage->setHeaders($headers);

        $this->session->createProducer()->send($destination, $transportMessage);
    }
}
