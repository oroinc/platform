<?php
namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\Session;

interface MessageProcessor
{
    const ACK = 'oro.message_queue.consumption.ack';
    
    const REJECT = 'oro.message_queue.consumption.reject';
    
    const REQUEUE = 'oro.message_queue.consumption.requeue';

    /**
     * @param Message $message
     * @param Session $session
     *
     * @return null|string
     */
    public function process(Message $message, Session $session);
}
