<?php

namespace Oro\Bundle\CacheBundle\Action\Handler;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorageInterface;

interface InvalidateCacheActionHandlerInterface
{
    /**
     * @param InvalidateCacheDataStorageInterface $dataStorage
     */
    public function handle(InvalidateCacheDataStorageInterface $dataStorage);
}
