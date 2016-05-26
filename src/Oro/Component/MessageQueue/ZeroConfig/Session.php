<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageProducer;
use Oro\Component\MessageQueue\Transport\Queue;

interface Session
{
    /**
     * @return MessageProducer
     */
    public function createProducer();

    /**
     * @return FrontProducer
     */
    public function createFrontProducer();

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
