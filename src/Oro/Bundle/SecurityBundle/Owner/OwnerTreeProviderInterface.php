<?php

namespace Oro\Bundle\SecurityBundle\Owner;

use Oro\Component\Config\Cache\ClearableConfigCacheInterface;
use Oro\Component\Config\Cache\WarmableConfigCacheInterface;

/**
 * The interface for owner tree providers.
 */
interface OwnerTreeProviderInterface extends WarmableConfigCacheInterface, ClearableConfigCacheInterface
{
    /**
     * Checks if this provider is suitable in the current security context.
     *
     * @return bool
     */
    public function supports(): bool;

    /**
     * Gets the owner tree.
     *
     * @return OwnerTreeInterface
     */
    public function getTree(): OwnerTreeInterface;
}
