<?php
namespace Oro\Component\MessageQueue\Client;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

interface MessageProcessorRegistryInterface
{
    /**
     * @param string $processorName
     *
     * @return MessageProcessorInterface
     */
    public function get($processorName);
}
