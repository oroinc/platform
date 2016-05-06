<?php
namespace Oro\Component\Messaging\Transport;

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
}
