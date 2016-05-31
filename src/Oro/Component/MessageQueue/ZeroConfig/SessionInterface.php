<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\QueueInterface;

interface SessionInterface
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
