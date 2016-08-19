<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\Dbal\DbalDestination;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;
use Oro\Component\MessageQueue\Transport\QueueInterface;

class DbalDriver implements DriverInterface
{
    /**
     * @var DbalSession
     */
    private $session;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param DbalSession $session
     * @param Config      $config
     */
    public function __construct(DbalSession $session, Config $config)
    {
        $this->session = $session;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @param DbalDestination $queue
     */
    public function send(QueueInterface $queue, Message $message)
    {
        $headers = $message->getHeaders();
        $properties = $message->getProperties();

        $headers['content_type'] = $message->getContentType();

        $transportMessage = $this->session->createMessage();
        $transportMessage->setBody($message->getBody());
        $transportMessage->setHeaders($headers);
        $transportMessage->setProperties($properties);

        $transportMessage->setMessageId($message->getMessageId());
        $transportMessage->setTimestamp($message->getTimestamp());

        if ($message->getDelaySec()) {
            $transportMessage->setDelay($message->getDelaySec());
        }

        if ($message->getPriority()) {
            $this->setMessagePriority($transportMessage, $message->getPriority());
        }

        if ($message->getExpireSec()) {
            throw new \InvalidArgumentException('Expire is not supported by the transport');
        }

        $this->session->createProducer()->send($queue, $transportMessage);
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalDestination
     */
    public function createQueue($queueName)
    {
        $queue = $this->session->createQueue($queueName);

        $this->session->declareQueue($queue);

        return $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param DbalMessage $message
     * @param string $priority
     */
    private function setMessagePriority(DbalMessage $message, $priority)
    {
        $map = [
            MessagePriority::VERY_LOW => 0,
            MessagePriority::LOW => 1,
            MessagePriority::NORMAL => 2,
            MessagePriority::HIGH => 3,
            MessagePriority::VERY_HIGH => 4,
        ];

        if (false == array_key_exists($priority, $map)) {
            throw new \InvalidArgumentException(sprintf(
                'Given priority could not be converted to transport\'s one. Got: %s',
                $priority
            ));
        }

        $message->setPriority($map[$priority]);
    }
}
