<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Message;

interface SessionInterface
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
}
