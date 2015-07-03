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
     * @return OwnerTree
     *
     * @throws \Exception
     */
    public function getTree();
}
