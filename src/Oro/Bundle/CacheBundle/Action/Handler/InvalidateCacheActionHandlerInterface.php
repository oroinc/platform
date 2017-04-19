<?php

namespace Oro\Bundle\CacheBundle\Action\Handler;

use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;

interface InvalidateCacheActionHandlerInterface
{
    /**
     * @param DataStorageInterface $dataStorage
     */
    public function handle(DataStorageInterface $dataStorage);
}
