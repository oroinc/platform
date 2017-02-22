<?php

namespace Oro\Bundle\SecurityBundle\Filter;

use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

class AclPrivilegeEntityFilter implements AclPrivilegeConfigurableFilterInterface
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function isSupported(AclPrivilege $aclPrivileges)
    {
        $identity = $aclPrivileges->getIdentity();
        return ObjectIdentityHelper::getExtensionKeyFromIdentityString($identity->getId()) === 'entity';
    }
}
