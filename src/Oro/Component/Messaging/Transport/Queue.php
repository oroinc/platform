<?php
namespace Oro\Component\Messaging\Transport;

interface Queue extends Destination
{
    /**
     * @return string
     */
    public function getQueueName();
}
