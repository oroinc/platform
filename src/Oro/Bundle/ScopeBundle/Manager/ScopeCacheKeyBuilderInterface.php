<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * Represents a builder of the cache key that is used to cache scope data loaded from the database.
 */
interface ScopeCacheKeyBuilderInterface
{
    public function getCacheKey(ScopeCriteria $criteria): ?string;
}
