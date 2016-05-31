<?php
namespace Oro\Component\MessageQueue\Transport;

interface QueueInterface extends DestinationInterface
{
    /**
     * @return string
     */
    public function getQueueName();
}
