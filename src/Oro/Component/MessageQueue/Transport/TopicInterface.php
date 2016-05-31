<?php
namespace Oro\Component\MessageQueue\Transport;

interface TopicInterface extends DestinationInterface
{
    /**
     * @return string
     */
    public function getTopicName();
}
