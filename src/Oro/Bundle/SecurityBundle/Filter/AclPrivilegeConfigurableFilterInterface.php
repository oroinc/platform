<?php

namespace Oro\Bundle\SecurityBundle\Filter;

use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

/**
 * Defines the contract for filtering ACL privileges based on configurability.
 *
 * Implementations of this interface filter ACL privileges to determine which ones
 * should be displayed or processed based on whether they are configurable according
 * to the provided configurable permission settings.
 */
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
