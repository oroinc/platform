<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Oro\Bundle\ScopeBundle\Model\NormalizeParameterValueTrait;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

/**
 * The trait that can be used by builders of the cache key depends on a scope criteria parameter(s).
 */
trait ScopeCacheKeyBuilderTrait
{
    use NormalizeParameterValueTrait;

    /** @var ScopeCacheKeyBuilderInterface */
    private $innerBuilder;

    public function __construct(ScopeCacheKeyBuilderInterface $innerBuilder)
    {
        $this->innerBuilder = $innerBuilder;
    }

    private function buildCacheKey(ScopeCriteria $criteria, string $paramName): ?string
    {
        $cacheKey = $this->innerBuilder->getCacheKey($criteria);
        if (null !== $cacheKey) {
            $params = $criteria->toArray();
            if (isset($params[$paramName])) {
                $id = $this->normalizeParameterValue($params[$paramName]);
                if (null !== $id) {
                    $cacheKey .= sprintf(';%s=%s', $paramName, $id);
                }
            }
        }

        return $cacheKey;
    }
}
