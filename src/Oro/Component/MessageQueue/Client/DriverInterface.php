<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;

interface DriverInterface
{
    /**
     * @return MessageProducer
     */
    public function createProducer();

    /**
     * @return MessageInterface
     */
    public function createMessage();

    /**
     * @param MessageInterface $message
     * @param int $priority
     *
     * @return void
     */
    public function setMessagePriority(MessageInterface $message, $priority);

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
