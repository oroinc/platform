<?php
namespace Oro\Component\MessageQueue\Transport;

interface MessageProducer
{
    /**
     * @param Destination $destination
     * @param Message $message
     *
     * @return void
     */
    public function send(Destination $destination, Message $message);
}