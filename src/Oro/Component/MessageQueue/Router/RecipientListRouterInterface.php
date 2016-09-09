<?php
namespace Oro\Component\MessageQueue\Router;

use Oro\Component\MessageQueue\Transport\MessageInterface;

interface RecipientListRouterInterface
{
    /**
     * @param MessageInterface $message
     *
     * @return \Traversable|Recipient[]
     */
    public function route(MessageInterface $message);
}
