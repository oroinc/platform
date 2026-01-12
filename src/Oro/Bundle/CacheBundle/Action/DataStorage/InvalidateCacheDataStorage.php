<?php

namespace Oro\Bundle\CacheBundle\Action\DataStorage;

use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Stores and manages cache invalidation parameters for scheduled cache invalidation actions.
 *
 * This class extends Symfony's {@see ParameterBag} to provide a specialized data storage container
 * for cache invalidation operations. It implements the {@see DataStorageInterface} to ensure
 * compatibility with the cache invalidation workflow, allowing parameters to be stored,
 * retrieved, and iterated over during the cache invalidation scheduling process.
 */
class InvalidateCacheDataStorage extends ParameterBag implements DataStorageInterface
{
}
