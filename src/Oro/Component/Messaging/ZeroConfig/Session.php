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
     * @return ProducerInterface
     */
    public function createFrontProducer();

    /**
     * @return ProducerInterface
     */
    public function createQueueProducer();

    /**
     * @return Topic
     */
    public function createFrontTopic();

    /**
     * @return Queue
     */
    public function createFrontQueue();

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
