<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\Dbal\DbalDestination;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Dbal\DbalSession;
use Oro\Component\MessageQueue\Transport\MessageInterface;
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
     */
    public function createProducer()
    {
        return new MessageProducer($this->session->createProducer(), $this);
    }

    /**
     * {@inheritdoc}
     *
     * @return DbalMessage
     */
    public function createMessage()
    {
        return $this->session->createMessage();
    }

    /**
     * {@inheritdoc}
     *
     * @param DbalMessage $message
     */
    public function setMessagePriority(MessageInterface $message, $priority)
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
     *
     * @param DbalMessage $message
     */
    public function delayMessage(QueueInterface $queue, MessageInterface $message, $delaySec)
    {
        $delayMessage = $this->session->createMessage(
            $message->getBody(),
            $message->getProperties(),
            $message->getHeaders()
        );
        $delayMessage->setDelay($delaySec);

        $this->session->createProducer()->send($queue, $delayMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        return $this->config;
    }
}
