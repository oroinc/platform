<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\UserBundle\Entity\AbstractRole;

trait SetRolePermissionsTrait
{
    /**
     * @param AclManager   $aclManager
     * @param AbstractRole $role
     * @param array        $aclData [oid descriptor => [permission, ...], ...]
     */
    private function setPermissions(AclManager $aclManager, AbstractRole $role, array $aclData)
    {
        $sid = $aclManager->getSid($role);
        foreach ($aclData as $oidDescriptor => $permissions) {
            $oid = $aclManager->getOid($oidDescriptor);
            $maskBuilders = $aclManager->getAllMaskBuilders($oid);
            foreach ($maskBuilders as $maskBuilder) {
                foreach ($permissions as $permission) {
                    if ($maskBuilder->hasMaskForPermission($permission)) {
                        $maskBuilder->add($permission);
                    }
                }
                $aclManager->setPermission($sid, $oid, $maskBuilder->get());
            }
        }
    }
}
