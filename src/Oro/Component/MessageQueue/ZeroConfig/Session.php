<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageProducer as TransportMessageProducer;
use Oro\Component\MessageQueue\Transport\Queue;

interface Session
{
    /**
     * @return TransportMessageProducer
     */
    public function createTransportMessageProducer();

    /**
     * @return MessageProducer
     */
    public function createMessageProducer();

    /**
     * @return Message
     */
    public function createMessage();

    /**
     * @param string $queueName
     *
     * @return Queue
     */
    public function createQueue($queueName);

    /**
     * @return Config
     */
    public function getConfig();
}
