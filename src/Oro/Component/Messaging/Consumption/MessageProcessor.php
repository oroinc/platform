<?php
namespace Oro\Component\Messaging\Consumption;

use Oro\Component\Messaging\Transport\Message;
use Oro\Component\Messaging\Transport\Session;

interface MessageProcessor
{
    const ACK = 'oro.messaging.consumption.ack';
    
    const REJECT = 'oro.messaging.consumption.reject';
    
    const REQUEUE = 'oro.messaging.consumption.requeue';

    /**
     * @param Message $message
     * @param Session $session
     *
     * @return null|string
     */
    public function process(Message $message, Session $session);
}
