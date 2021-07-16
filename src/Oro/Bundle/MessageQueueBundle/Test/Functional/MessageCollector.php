<?php

namespace Oro\Bundle\MessageQueueBundle\Test\Functional;

use Oro\Bundle\MessageQueueBundle\Test\MessageCollector as BaseMessageCollector;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

/**
 * This class is intended to be used in functional tests and allows to get sent messages.
 */
class MessageCollector extends BaseMessageCollector
{
    public function __construct(MessageProducerInterface $messageProducer)
    {
        parent::__construct($messageProducer);
    }
}
