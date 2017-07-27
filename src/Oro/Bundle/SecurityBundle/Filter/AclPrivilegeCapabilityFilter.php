<?php

namespace Oro\Bundle\SecurityBundle\Filter;

use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\SecurityBundle\Model\ConfigurablePermission;

class AclPrivilegeCapabilityFilter implements AclPrivilegeConfigurableFilterInterface
{
    /**
     * {@inheritdoc}
     */
    public function filter(AclPrivilege $aclPrivilege, ConfigurablePermission $configurablePermission)
    {
        $identity = $aclPrivilege->getIdentity();
        $capability = ObjectIdentityHelper::getClassFromIdentityString($identity->getId());

        return $configurablePermission->isCapabilityConfigurable($capability);
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported(AclPrivilege $aclPrivileges)
    {
        $identity = $aclPrivileges->getIdentity();

        return ObjectIdentityHelper::getExtensionKeyFromIdentityString($identity->getId()) === 'action';
    }
}
