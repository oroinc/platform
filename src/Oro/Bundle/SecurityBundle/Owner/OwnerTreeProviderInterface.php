<?php

namespace Oro\Bundle\SecurityBundle\Owner;

interface OwnerTreeProviderInterface
{
    /**
     * @return bool
     */
    public function supports();

    /**
     * Get ACL tree
     *
     * @return OwnerTreeInterface
     *
     * @throws \Exception If ACL tree cache not warmed
     */
    public function getTree();

    /**
     * Clear the owner tree cache
     */
    public function clear();

    /**
     * Warmup owner tree cache
     */
    public function warmUpCache();
}
