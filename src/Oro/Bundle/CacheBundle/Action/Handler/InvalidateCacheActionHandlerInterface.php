<?php

namespace Oro\Bundle\CacheBundle\Action\Handler;

use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;

interface InvalidateCacheActionHandlerInterface
{
    public function handle(DataStorageInterface $dataStorage);
}
