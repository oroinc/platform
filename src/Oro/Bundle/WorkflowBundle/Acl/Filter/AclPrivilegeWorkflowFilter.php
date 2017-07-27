<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Filter;

use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Filter\AclPrivilegeConfigurableFilterInterface;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

class AclPrivilegeWorkflowFilter implements AclPrivilegeConfigurableFilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(AclPrivilege $aclPrivilege, ConfigurablePermission $configurablePermission)
    {
        $workflowName = ObjectIdentityHelper::getClassFromIdentityString($aclPrivilege->getIdentity()->getId());

        foreach ($aclPrivilege->getPermissions() as $permissionName => $permission) {
            if (!$configurablePermission->isWorkflowPermissionConfigurable($workflowName, $permissionName)) {
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
        
        return ObjectIdentityHelper::getExtensionKeyFromIdentityString($identity->getId()) === 'workflow';
    }
}
