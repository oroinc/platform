<?php
namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

interface MessageProcessorInterface
{
    const ACK = 'oro.message_queue.consumption.ack';
    
    const REJECT = 'oro.message_queue.consumption.reject';
    
    const REQUEUE = 'oro.message_queue.consumption.requeue';

    /**
     * @param MessageInterface $message
     * @param SessionInterface $session
     *
     * @return null|string
     */
    public function process(MessageInterface $message, SessionInterface $session);
}
