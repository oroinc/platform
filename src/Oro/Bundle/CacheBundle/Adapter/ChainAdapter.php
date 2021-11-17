<?php

namespace Oro\Bundle\CacheBundle\Adapter;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\ChainAdapter as SymfonyChainAdapter;

/**
 * This class extends Symfony ChainAdapter in order to remove in memory adapter (ArrayAdapter) from CLI requests
 */
class ChainAdapter extends SymfonyChainAdapter
{
    public function __construct(array $adapters, int $defaultLifetime = 0)
    {
        foreach ($adapters as $i => $adapter) {
            if (PHP_SAPI === 'cli' && $adapter instanceof ArrayAdapter) {
                unset($adapters[$i]);
            }
        }
        parent::__construct($adapters, $defaultLifetime);
    }
}
