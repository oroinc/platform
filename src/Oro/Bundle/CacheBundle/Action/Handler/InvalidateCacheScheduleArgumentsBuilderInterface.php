<?php

namespace Oro\Bundle\CacheBundle\Action\Handler;

use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;

interface InvalidateCacheScheduleArgumentsBuilderInterface
{
    /**
     * @param DataStorageInterface $dataStorage
     *
     * @return string[]
     */
    public function build(DataStorageInterface $dataStorage);
}
