<?php
namespace Oro\Component\MessageQueue\Transport;

interface MessageProducerInterface
{
    /**
     * @param DestinationInterface $destination
     * @param MessageInterface $message
     *
     * @return void
     */
    public function send(DestinationInterface $destination, MessageInterface $message);
}