<?php

namespace Oro\Bundle\CacheBundle\Action\DataStorage;

use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class InvalidateCacheDataStorage extends ParameterBag implements DataStorageInterface
{
}
