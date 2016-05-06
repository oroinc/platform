<?php
namespace Oro\Component\Messaging\Transport;

interface Topic extends Destination
{
    /**
     * @return string
     */
    public function getTopicName();
}
