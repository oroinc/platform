<?php
namespace Oro\Component\MessageQueue\Transport\Null;

use Oro\Component\MessageQueue\Transport\DestinationInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\MessageProducerInterface;

class NullMessageProducer implements MessageProducerInterface
{
    /**
     * {@inheritdoc}
     */
    public function send(DestinationInterface $destination, MessageInterface $message)
    {
    }
}
