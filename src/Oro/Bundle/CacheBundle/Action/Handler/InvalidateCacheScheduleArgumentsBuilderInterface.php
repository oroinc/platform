<?php

namespace Oro\Bundle\CacheBundle\Action\Handler;

use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;

/**
 * Defines the contract for building command-line arguments for scheduled cache invalidation.
 *
 * Implementations of this interface are responsible for extracting relevant parameters
 * from a {@see DataStorageInterface} instance and converting them into command-line arguments
 * suitable for execution by a scheduled cache invalidation command. This allows the
 * cache invalidation parameters to be serialized and passed to background jobs or
 * scheduled tasks.
 */
interface InvalidateCacheScheduleArgumentsBuilderInterface
{
    /**
     * @param DataStorageInterface $dataStorage
     *
     * @return string[]
     */
    public function build(DataStorageInterface $dataStorage);
}
