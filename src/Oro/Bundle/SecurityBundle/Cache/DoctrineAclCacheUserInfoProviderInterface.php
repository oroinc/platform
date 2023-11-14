<?php

namespace Oro\Bundle\SecurityBundle\Cache;

/**
 * Provider that returns cache info from current token for Doctrine ACL query cache.
 */
interface DoctrineAclCacheUserInfoProviderInterface
{
    /**
     * @return array [className, entityId]
     */
    public function getCurrentUserCacheKeyInfo(): array;
}
