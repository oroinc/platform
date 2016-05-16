<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Transport\Message;

interface ProducerInterface
{
    /**
     * @param Message $message
     */
    public function send(Message $message);
}
