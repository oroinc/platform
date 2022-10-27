<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * The default implementation of the scope cache key builder.
 * This implementation disables the caching scope data loaded from the database.
 */
class ScopeCacheKeyBuilder implements ScopeCacheKeyBuilderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getCacheKey(ScopeCriteria $criteria): ?string
    {
        return null;
    }
}
