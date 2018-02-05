<?php

namespace Oro\Bundle\CacheBundle\Provider;

use Doctrine\Common\Cache\ChainCache;

class MemoryCacheChain extends ChainCache
{
    /**
     * {@inheritdoc}
     */
    public function __construct($cacheProviders = [])
    {
        if (PHP_SAPI !== 'cli') {
            array_unshift($cacheProviders, new ArrayCache());
        }

        parent::__construct($cacheProviders);
    }
}
