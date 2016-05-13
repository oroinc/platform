<?php
namespace Oro\Component\Messaging\ZeroConfig;

use Oro\Component\Messaging\Consumption\MessageProcessor;
use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\Session;

class ConsumerReceiver implements MessageProcessor
{
    /**
     * {@inheritdoc}
     */
    public function process(Message $message, Session $session)
    {
        $messageName = $message->getProperty('messageName');
        $handlerName = $message->getProperty('handlerName');

        $this->handlerRegistry->get($handlerName)->process($message->getBody()); // ?????
    }
}
