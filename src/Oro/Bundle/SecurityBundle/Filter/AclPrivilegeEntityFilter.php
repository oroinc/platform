<?php

namespace Oro\Bundle\SecurityBundle\Filter;

use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

/**
 * Filters ACL privileges based on entity permission configurability.
 *
 * This filter removes permissions from ACL privileges that are not configurable
 * for the entity according to the provided configurable permission settings. If all
 * permissions are removed, the entire privilege is filtered out.
 */
class AclPrivilegeEntityFilter implements AclPrivilegeConfigurableFilterInterface
{
    #[\Override]
    public function filter(AclPrivilege $aclPrivilege, ConfigurablePermission $configurablePermission)
    {
        $entityClass = ObjectIdentityHelper::getClassFromIdentityString($aclPrivilege->getIdentity()->getId());

        foreach ($aclPrivilege->getPermissions() as $permissionName => $permission) {
            if (!$configurablePermission->isEntityPermissionConfigurable($entityClass, $permissionName)) {
                $aclPrivilege->removePermission($permission);
            }
        }

        return $aclPrivilege->hasPermissions();
    }

    #[\Override]
    public function isSupported(AclPrivilege $aclPrivileges)
    {
        $identity = $aclPrivileges->getIdentity();
        return ObjectIdentityHelper::getExtensionKeyFromIdentityString($identity->getId()) === 'entity';
    }
}
