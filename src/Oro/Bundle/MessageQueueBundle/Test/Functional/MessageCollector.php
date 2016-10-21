<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Bundle\MessageQueueBundle\Test\MessageCollector as BaseMessageCollector;

class MessageCollector extends BaseMessageCollector
{
    /**
     * @param MessageProducerInterface $messageProducer
     */
    public function __construct(MessageProducerInterface $messageProducer)
    {
        parent::__construct($messageProducer);
    }
}
