<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\MessageProducer;
use Oro\Component\Messaging\Transport\Queue;

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
