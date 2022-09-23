<?php

declare(strict_types=1);

namespace Oro\Bundle\SecurityBundle\Test\Functional;

use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides handy methods for functional tests that check ACL.
 *
 * @method static ContainerInterface getContainer()
 */
trait AclAwareTestTrait
{
    protected static function updateRolePermissions(string $role, array $permissionsByObjectIdentity): void
    {
        /** @var AclManager $aclManager */
        $aclManager = self::getContainer()->get('oro_security.acl.manager');

        $sid = $aclManager->getSid($role);
        foreach ($permissionsByObjectIdentity as $oid => $permissions) {
            $oid = $aclManager->getOid($oid);
            $maskBuilder = $aclManager->getMaskBuilder($oid);
            foreach ($permissions as $permission) {
                $maskBuilder->add($permission);
            }
            $aclManager->setPermission($sid, $oid, $maskBuilder->get());
        }

        $aclManager->flush();
    }
}
