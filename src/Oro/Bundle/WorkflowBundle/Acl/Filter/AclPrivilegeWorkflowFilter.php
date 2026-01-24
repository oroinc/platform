<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Filter;

use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Filter\AclPrivilegeConfigurableFilterInterface;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

/**
 * Filters workflow-related ACL privileges based on configurable permissions.
 *
 * This filter removes permissions from workflow ACL privileges that are not
 * configurable according to the provided {@see ConfigurablePermission} settings.
 */
class AclPrivilegeWorkflowFilter implements AclPrivilegeConfigurableFilterInterface
{
    #[\Override]
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

    #[\Override]
    public function isSupported(AclPrivilege $aclPrivileges)
    {
        $identity = $aclPrivileges->getIdentity();

        return ObjectIdentityHelper::getExtensionKeyFromIdentityString($identity->getId()) === 'workflow';
    }
}
