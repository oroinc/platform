<?php

namespace Oro\Bundle\SecurityBundle\Filter;

use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

interface AclPrivilegeConfigurableFilterInterface
{
    /**
     * @param AclPrivilege $aclPrivilege
     * @param ConfigurablePermission $configurablePermission
     *
     * @return bool
     */
    public function filter(AclPrivilege $aclPrivilege, ConfigurablePermission $configurablePermission);

    /**
     * @param AclPrivilege $aclPrivileges
     * @return bool
     */
    public function isSupported(AclPrivilege $aclPrivileges);
}
