<?php
namespace Oro\Component\MessageQueue\Consumption;

use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

interface MessageProcessorInterface
{
    /**
     * Use this constant when the message is processed successfully and the message could be removed from the queue.
     */
    const ACK = 'oro.message_queue.consumption.ack';

    /**
     * Use this constant when the message is not valid or could not be processed
     * The message is removed from the queue
     */
    const REJECT = 'oro.message_queue.consumption.reject';

    /**
     * Use this constant when the message is not valid or could not be processed right now but we can try again later
     * The original message is removed from the queue but a copy is publsihed to the queue again.
     */
    const REQUEUE = 'oro.message_queue.consumption.requeue';

    /**
     * @param MessageInterface $message
     * @param SessionInterface $session
     *
     * @return null|string
     */
    public function process(MessageInterface $message, SessionInterface $session);
}
