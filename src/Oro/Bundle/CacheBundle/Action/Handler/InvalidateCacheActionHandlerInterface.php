<?php

namespace Oro\Bundle\CacheBundle\Action\Handler;

use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;

/**
 * Defines the contract for handling cache invalidation actions.
 *
 * Implementations of this interface are responsible for executing cache invalidation
 * operations based on parameters provided through a {@see DataStorageInterface} instance.
 * This allows different cache invalidation strategies to be plugged in and executed
 * in a consistent manner within the cache invalidation workflow.
 */
interface InvalidateCacheActionHandlerInterface
{
    public function handle(DataStorageInterface $dataStorage);
}
