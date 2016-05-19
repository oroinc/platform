<?php
namespace Oro\Component\Messaging\Router;

use Oro\Component\Messaging\Transport\Message;

interface Router
{
    /**
     * @param Message $message
     *
     * @return \Traversable|Recipient[]
     */
    public function route(Message $message);
}
