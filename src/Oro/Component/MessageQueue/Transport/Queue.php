<?php
namespace Oro\Component\MessageQueue\Transport;

interface Queue extends Destination
{
    /**
     * @return string
     */
    public function getQueueName();
}
