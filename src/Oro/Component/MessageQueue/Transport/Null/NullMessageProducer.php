<?php
namespace Oro\Component\MessageQueue\Transport\Null;

use Oro\Component\MessageQueue\Transport\Destination;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageProducer;

class NullMessageProducer implements MessageProducer
{
    /**
     * {@inheritdoc}
     */
    public function send(Destination $destination, Message $message)
    {
    }
}
