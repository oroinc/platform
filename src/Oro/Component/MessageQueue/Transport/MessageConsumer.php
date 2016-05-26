<?php
namespace Oro\Component\MessageQueue\Transport;

interface MessageConsumer
{
    /**
     * @return Queue
     */
    public function getQueue();

    /**
     * @param int $timeout
     *
     * @return Message
     */
    public function receive($timeout = 0);

    /**
     * @param Message $message
     *
     * @return void
     */
    public function acknowledge(Message $message);

    /**
     * @param Message $message
     * @param bool $requeue
     *
     * @return void
     */
    public function reject(Message $message, $requeue = false);
}
