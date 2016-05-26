<?php
namespace Oro\Component\MessageQueue\Router;

use Oro\Component\MessageQueue\Transport\Message;

interface Router
{
    /**
     * @param Message $message
     *
     * @return \Traversable|Recipient[]
     */
    public function route(Message $message);
}
