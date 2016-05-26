<?php
namespace Oro\Component\MessageQueue\Transport;

interface Topic extends Destination
{
    /**
     * @return string
     */
    public function getTopicName();
}
