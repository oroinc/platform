<?php
namespace Oro\Component\Messaging\Transport;

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