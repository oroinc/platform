<?php

namespace Oro\Bundle\SecurityBundle\Acl\Group;

interface AclGroupProviderInterface
{
    const DEFAULT_SECURITY_GROUP = '';

    /**
     * @return bool
     */
    public function supports();

    /**
     * @return string
     */
    public function getGroup();
}
