<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Stub;

use Oro\Bundle\ScopeBundle\Manager\ScopeCacheKeyBuilderInterface;
use Oro\Bundle\ScopeBundle\Manager\ScopeCacheKeyBuilderTrait;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class TestScopeCacheKeyBuilder implements ScopeCacheKeyBuilderInterface
{
    use ScopeCacheKeyBuilderTrait;

    /**
     * {@inheritDoc}
     */
    public function getCacheKey(ScopeCriteria $criteria): ?string
    {
        return $this->buildCacheKey($criteria, 'testParam');
    }
}
