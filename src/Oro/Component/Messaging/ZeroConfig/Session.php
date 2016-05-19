<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\Queue;
use Oro\Component\Messaging\Transport\Topic;
use Oro\Component\Messaging\Transport\Session as TransportSession;

interface Session
{
    /**
     * @return Message
     */
    public function createMessage();

    /**
     * @return FrontProducer
     */
    public function createFrontProducer();

    /**
     * @return QueueProducer
     */
    public function createQueueProducer();

    /**
     * @return Topic
     */
    public function createRouterTopic();

    /**
     * @return Queue
     */
    public function createRouterQueue();

    /**
     * @param string $queueName
     * 
     * @return Topic
     */
    public function createQueueTopic($queueName);

    /**
     * @param string $queueName
     * 
     * @return Queue
     */
    public function createQueueQueue($queueName);

    /**
     * @return TransportSession
     */
    public function getTransportSession();
}
