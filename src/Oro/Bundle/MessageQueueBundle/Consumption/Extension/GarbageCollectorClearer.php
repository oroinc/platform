<?php

namespace Oro\Bundle\MessageQueueBundle\Consumption\Extension;

use Psr\Log\LoggerInterface;

/**
 * Executes "gc_collect_cycles" to force clear of the memory
 * and prevent segmentation fault that may sometimes occur in "unserialize" function.
 */
class GarbageCollectorClearer implements ClearerInterface
{
    /**
     * {@inheritdoc}
     */
    public function clear(LoggerInterface $logger)
    {
        gc_collect_cycles();
    }
}
