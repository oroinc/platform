<?php
namespace Oro\Component\MessageQueue\ZeroConfig;

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
