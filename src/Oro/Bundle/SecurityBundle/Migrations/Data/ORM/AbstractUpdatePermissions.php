<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Data\ORM;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Extension\ActionAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityAclExtension;
use Oro\Bundle\SecurityBundle\Acl\Extension\ObjectIdentityHelper;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclManager;
use Oro\Bundle\SecurityBundle\Acl\Persistence\AclPrivilegeRepository;
use Oro\Bundle\SecurityBundle\Model\AclPermission;
use Oro\Bundle\SecurityBundle\Model\AclPrivilege;
use Oro\Bundle\UserBundle\Entity\AbstractRole;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * The base class for data fixtures that update permissions for roles.
 */
abstract class AbstractUpdatePermissions extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @return AclManager
     */
    protected function getAclManager()
    {
        return $this->container->get('oro_security.acl.manager');
    }

    /**
     * @return AclPrivilegeRepository
     */
    protected function getAclPrivilegeRepository()
    {
        return $this->container->get('oro_security.acl.privilege_repository');
    }

    /**
     * @param ObjectManager $manager
     * @param string        $roleName
     * @param string|null   $roleEntityClass
     *
     * @return AbstractRole|null
     */
    protected function getRole(ObjectManager $manager, string $roleName, string $roleEntityClass = null)
    {
        return $manager->getRepository($roleEntityClass ?? Role::class)->findOneBy(['role' => $roleName]);
    }

    /**
     * @param SecurityIdentityInterface $sid
     * @param string                    $aclGroup
     *
     * @return ArrayCollection|AclPrivilege[]
     */
    protected function getPrivileges(SecurityIdentityInterface $sid, string $aclGroup = null)
    {
        return $this->getAclPrivilegeRepository()->getPrivileges($sid, $aclGroup);
    }

    /**
     * @param ArrayCollection|AclPrivilege[] $privileges
     * @param string                         $oidDescriptor
     *
     * @return ArrayCollection|AclPermission[]
     */
    protected function getPrivilegePermissions(ArrayCollection $privileges, string $oidDescriptor)
    {
        foreach ($privileges as $privilege) {
            if ($privilege->getIdentity()->getId() === $oidDescriptor) {
                return $privilege->getPermissions();
            }
        }

        return new ArrayCollection([]);
    }

    /**
     * @param AclPrivilege $privilege
     * @param string       $permission
     *
     * @return string|null
     */
    protected function getPermissionAccessLevelName(AclPrivilege $privilege, string $permission)
    {
        /** @var AclPermission|null $permissionObject */
        $permissionObject = $privilege->getPermissions()->get($permission);
        if (null === $permissionObject) {
            return null;
        }

        return AccessLevel::getAccessLevelName($permissionObject->getAccessLevel());
    }

    /**
     * @param AclManager        $aclManager
     * @param AbstractRole|null $role
     * @param string            $entityClass
     * @param string[]          $permissions
     */
    protected function setEntityPermissions(
        AclManager $aclManager,
        ?AbstractRole $role,
        string $entityClass,
        array $permissions
    ) {
        if (null === $role) {
            return;
        }

        $sid = $aclManager->getSid($role);
        $oid = $aclManager->getOid(
            ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, $entityClass)
        );
        $maskBuilders = $aclManager->getAllMaskBuilders($oid);
        foreach ($maskBuilders as $maskBuilder) {
            $hasChanges = false;
            foreach ($permissions as $permission) {
                if ($maskBuilder->hasMaskForPermission($permission)) {
                    $maskBuilder->add($permission);
                    $hasChanges = true;
                }
            }
            if ($hasChanges) {
                $aclManager->setPermission($sid, $oid, $maskBuilder->get());
            }
        }
    }

    /**
     * @param AclManager        $aclManager
     * @param AbstractRole|null $role
     * @param string            $entityClass
     * @param string[]          $permissions
     */
    protected function replaceEntityPermissions(
        AclManager $aclManager,
        ?AbstractRole $role,
        string $entityClass,
        array $permissions
    ) {
        if (null === $role) {
            return;
        }

        $sid = $aclManager->getSid($role);
        $oid = $aclManager->getOid(
            ObjectIdentityHelper::encodeIdentityString(EntityAclExtension::NAME, $entityClass)
        );
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

    /**
     * @param AclManager        $aclManager
     * @param AbstractRole|null $role
     * @param ObjectIdentity    $oid
     * @param string[]          $permissions
     */
    protected function replacePermissions(
        AclManager $aclManager,
        ?AbstractRole $role,
        ObjectIdentity $oid,
        array $permissions
    ) {
        if (null === $role) {
            return;
        }

        $sid = $aclManager->getSid($role);
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

    /**
     * @param AclManager        $aclManager
     * @param AbstractRole|null $role
     * @param string[]          $actions
     */
    protected function enableActions(
        AclManager $aclManager,
        ?AbstractRole $role,
        array $actions
    ) {
        if (null === $role) {
            return;
        }

        $sid = $aclManager->getSid($role);
        foreach ($actions as $action) {
            $oid = $aclManager->getOid(
                ObjectIdentityHelper::encodeIdentityString(ActionAclExtension::NAME, $action)
            );
            $maskBuilder = $aclManager->getMaskBuilder($oid);
            $maskBuilder->add('EXECUTE');
            $aclManager->setPermission($sid, $oid, $maskBuilder->get());
        }
    }
}
