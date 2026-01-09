<?php

namespace Oro\Bundle\SecurityBundle\Filter;

use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

/**
 * Filters ACL privileges based on capability configurability.
 *
 * This filter determines whether an ACL privilege for a capability should be included
 * in the privilege list based on whether the capability is configurable according to
 * the provided configurable permission settings.
 */
class AclPrivilegeCapabilityFilter implements AclPrivilegeConfigurableFilterInterface
{
    #[\Override]
    public function filter(AclPrivilege $aclPrivilege, ConfigurablePermission $configurablePermission)
    {
        $identity = $aclPrivilege->getIdentity();
        $capability = ObjectIdentityHelper::getClassFromIdentityString($identity->getId());

        return $configurablePermission->isCapabilityConfigurable($capability);
    }

    #[\Override]
    public function isSupported(AclPrivilege $aclPrivileges)
    {
        $identity = $aclPrivileges->getIdentity();

        return ObjectIdentityHelper::getExtensionKeyFromIdentityString($identity->getId()) === 'action';
    }
}
