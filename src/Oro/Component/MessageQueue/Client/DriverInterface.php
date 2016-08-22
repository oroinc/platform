<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;

interface DriverInterface
{
    /**
     * @return MessageInterface
     */
    public function createTransportMessage();

    /**
     * @param QueueInterface $queue
     * @param Message $message
     */
    public function send(QueueInterface $queue, Message $message);

    /**
     * @param string $queueName
     *
     * @return QueueInterface
     */
    public function createQueue($queueName);

    /**
     * @return Config
     */
    public function getConfig();
}
