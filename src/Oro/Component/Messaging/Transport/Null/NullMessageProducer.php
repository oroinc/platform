<?php
namespace Oro\Component\Messaging\Transport\Null;

use Oro\Component\Messaging\Transport\Destination;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\MessageProducer;

class NullMessageProducer implements MessageProducer
{
    /**
     * {@inheritdoc}
     */
    public function send(Destination $destination, Message $message)
    {
    }
}
