<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

use Oro\Component\MessageQueue\Consumption\MessageProcessor;

interface MessageProcessorRegistryInterface
{
    /**
     * @param string $processorName
     *
     * @return MessageProcessor
     */
    public function get($processorName);
}
