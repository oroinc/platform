<?php

namespace Oro\Bundle\SecurityBundle\Owner;

interface OwnerTreeProviderInterface
{
    /**
     * @return bool
     */
    public function supports();

    /**
     * Gets the owner tree
     *
     * @return OwnerTreeInterface
     */
    public function getTree();

    /**
     * Clears the owner tree cache
     */
    public function clear();

    /**
     * Warmups the owner tree cache
     */
    public function warmUpCache();
}
